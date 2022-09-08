/**
 * @license almond 0.3.3 Copyright jQuery Foundation and other contributors.
 * Released under MIT license, http://github.com/requirejs/almond/LICENSE
 */
//Going sloppy to avoid 'use strict' string cost, but strict practices should
//be followed.
/*global setTimeout: false */

var requirejs, require, define;
(function (undef) {
    var main, req, makeMap, handlers,
        defined = {},
        waiting = {},
        config = {},
        defining = {},
        hasOwn = Object.prototype.hasOwnProperty,
        aps = [].slice,
        jsSuffixRegExp = /\.js$/;

    function hasProp(obj, prop) {
        return hasOwn.call(obj, prop);
    }

    /**
     * Given a relative module name, like ./something, normalize it to
     * a real name that can be mapped to a path.
     * @param {String} name the relative name
     * @param {String} baseName a real name that the name arg is relative
     * to.
     * @returns {String} normalized name
     */
    function normalize(name, baseName) {
        var nameParts, nameSegment, mapValue, foundMap, lastIndex,
            foundI, foundStarMap, starI, i, j, part, normalizedBaseParts,
            baseParts = baseName && baseName.split("/"),
            map = config.map,
            starMap = (map && map['*']) || {};

        //Adjust any relative paths.
        if (name) {
            name = name.split('/');
            lastIndex = name.length - 1;

            // If wanting node ID compatibility, strip .js from end
            // of IDs. Have to do this here, and not in nameToUrl
            // because node allows either .js or non .js to map
            // to same file.
            if (config.nodeIdCompat && jsSuffixRegExp.test(name[lastIndex])) {
                name[lastIndex] = name[lastIndex].replace(jsSuffixRegExp, '');
            }

            // Starts with a '.' so need the baseName
            if (name[0].charAt(0) === '.' && baseParts) {
                //Convert baseName to array, and lop off the last part,
                //so that . matches that 'directory' and not name of the baseName's
                //module. For instance, baseName of 'one/two/three', maps to
                //'one/two/three.js', but we want the directory, 'one/two' for
                //this normalization.
                normalizedBaseParts = baseParts.slice(0, baseParts.length - 1);
                name = normalizedBaseParts.concat(name);
            }

            //start trimDots
            for (i = 0; i < name.length; i++) {
                part = name[i];
                if (part === '.') {
                    name.splice(i, 1);
                    i -= 1;
                } else if (part === '..') {
                    // If at the start, or previous value is still ..,
                    // keep them so that when converted to a path it may
                    // still work when converted to a path, even though
                    // as an ID it is less than ideal. In larger point
                    // releases, may be better to just kick out an error.
                    if (i === 0 || (i === 1 && name[2] === '..') || name[i - 1] === '..') {
                        continue;
                    } else if (i > 0) {
                        name.splice(i - 1, 2);
                        i -= 2;
                    }
                }
            }
            //end trimDots

            name = name.join('/');
        }

        //Apply map config if available.
        if ((baseParts || starMap) && map) {
            nameParts = name.split('/');

            for (i = nameParts.length; i > 0; i -= 1) {
                nameSegment = nameParts.slice(0, i).join("/");

                if (baseParts) {
                    //Find the longest baseName segment match in the config.
                    //So, do joins on the biggest to smallest lengths of baseParts.
                    for (j = baseParts.length; j > 0; j -= 1) {
                        mapValue = map[baseParts.slice(0, j).join('/')];

                        //baseName segment has  config, find if it has one for
                        //this name.
                        if (mapValue) {
                            mapValue = mapValue[nameSegment];
                            if (mapValue) {
                                //Match, update name to the new value.
                                foundMap = mapValue;
                                foundI = i;
                                break;
                            }
                        }
                    }
                }

                if (foundMap) {
                    break;
                }

                //Check for a star map match, but just hold on to it,
                //if there is a shorter segment match later in a matching
                //config, then favor over this star map.
                if (!foundStarMap && starMap && starMap[nameSegment]) {
                    foundStarMap = starMap[nameSegment];
                    starI = i;
                }
            }

            if (!foundMap && foundStarMap) {
                foundMap = foundStarMap;
                foundI = starI;
            }

            if (foundMap) {
                nameParts.splice(0, foundI, foundMap);
                name = nameParts.join('/');
            }
        }

        return name;
    }

    function makeRequire(relName, forceSync) {
        return function () {
            //A version of a require function that passes a moduleName
            //value for items that may need to
            //look up paths relative to the moduleName
            var args = aps.call(arguments, 0);

            //If first arg is not require('string'), and there is only
            //one arg, it is the array form without a callback. Insert
            //a null so that the following concat is correct.
            if (typeof args[0] !== 'string' && args.length === 1) {
                args.push(null);
            }
            return req.apply(undef, args.concat([relName, forceSync]));
        };
    }

    function makeNormalize(relName) {
        return function (name) {
            return normalize(name, relName);
        };
    }

    function makeLoad(depName) {
        return function (value) {
            defined[depName] = value;
        };
    }

    function callDep(name) {
        if (hasProp(waiting, name)) {
            var args = waiting[name];
            delete waiting[name];
            defining[name] = true;
            main.apply(undef, args);
        }

        if (!hasProp(defined, name) && !hasProp(defining, name)) {
            throw new Error('No ' + name);
        }
        return defined[name];
    }

    //Turns a plugin!resource to [plugin, resource]
    //with the plugin being undefined if the name
    //did not have a plugin prefix.
    function splitPrefix(name) {
        var prefix,
            index = name ? name.indexOf('!') : -1;
        if (index > -1) {
            prefix = name.substring(0, index);
            name = name.substring(index + 1, name.length);
        }
        return [prefix, name];
    }

    //Creates a parts array for a relName where first part is plugin ID,
    //second part is resource ID. Assumes relName has already been normalized.
    function makeRelParts(relName) {
        return relName ? splitPrefix(relName) : [];
    }

    /**
     * Makes a name map, normalizing the name, and using a plugin
     * for normalization if necessary. Grabs a ref to plugin
     * too, as an optimization.
     */
    makeMap = function (name, relParts) {
        var plugin,
            parts = splitPrefix(name),
            prefix = parts[0],
            relResourceName = relParts[1];

        name = parts[1];

        if (prefix) {
            prefix = normalize(prefix, relResourceName);
            plugin = callDep(prefix);
        }

        //Normalize according
        if (prefix) {
            if (plugin && plugin.normalize) {
                name = plugin.normalize(name, makeNormalize(relResourceName));
            } else {
                name = normalize(name, relResourceName);
            }
        } else {
            name = normalize(name, relResourceName);
            parts = splitPrefix(name);
            prefix = parts[0];
            name = parts[1];
            if (prefix) {
                plugin = callDep(prefix);
            }
        }

        //Using ridiculous property names for space reasons
        return {
            f: prefix ? prefix + '!' + name : name, //fullName
            n: name,
            pr: prefix,
            p: plugin
        };
    };

    function makeConfig(name) {
        return function () {
            return (config && config.config && config.config[name]) || {};
        };
    }

    handlers = {
        require: function (name) {
            return makeRequire(name);
        },
        exports: function (name) {
            var e = defined[name];
            if (typeof e !== 'undefined') {
                return e;
            } else {
                return (defined[name] = {});
            }
        },
        module: function (name) {
            return {
                id: name,
                uri: '',
                exports: defined[name],
                config: makeConfig(name)
            };
        }
    };

    main = function (name, deps, callback, relName) {
        var cjsModule, depName, ret, map, i, relParts,
            args = [],
            callbackType = typeof callback,
            usingExports;

        //Use name if no relName
        relName = relName || name;
        relParts = makeRelParts(relName);

        //Call the callback to define the module, if necessary.
        if (callbackType === 'undefined' || callbackType === 'function') {
            //Pull out the defined dependencies and pass the ordered
            //values to the callback.
            //Default to [require, exports, module] if no deps
            deps = !deps.length && callback.length ? ['require', 'exports', 'module'] : deps;
            for (i = 0; i < deps.length; i += 1) {
                map = makeMap(deps[i], relParts);
                depName = map.f;

                //Fast path CommonJS standard dependencies.
                if (depName === "require") {
                    args[i] = handlers.require(name);
                } else if (depName === "exports") {
                    //CommonJS module spec 1.1
                    args[i] = handlers.exports(name);
                    usingExports = true;
                } else if (depName === "module") {
                    //CommonJS module spec 1.1
                    cjsModule = args[i] = handlers.module(name);
                } else if (hasProp(defined, depName) ||
                           hasProp(waiting, depName) ||
                           hasProp(defining, depName)) {
                    args[i] = callDep(depName);
                } else if (map.p) {
                    map.p.load(map.n, makeRequire(relName, true), makeLoad(depName), {});
                    args[i] = defined[depName];
                } else {
                    throw new Error(name + ' missing ' + depName);
                }
            }

            ret = callback ? callback.apply(defined[name], args) : undefined;

            if (name) {
                //If setting exports via "module" is in play,
                //favor that over return value and exports. After that,
                //favor a non-undefined return value over exports use.
                if (cjsModule && cjsModule.exports !== undef &&
                        cjsModule.exports !== defined[name]) {
                    defined[name] = cjsModule.exports;
                } else if (ret !== undef || !usingExports) {
                    //Use the return value from the function.
                    defined[name] = ret;
                }
            }
        } else if (name) {
            //May just be an object definition for the module. Only
            //worry about defining if have a module name.
            defined[name] = callback;
        }
    };

    requirejs = require = req = function (deps, callback, relName, forceSync, alt) {
        if (typeof deps === "string") {
            if (handlers[deps]) {
                //callback in this case is really relName
                return handlers[deps](callback);
            }
            //Just return the module wanted. In this scenario, the
            //deps arg is the module name, and second arg (if passed)
            //is just the relName.
            //Normalize module name, if it contains . or ..
            return callDep(makeMap(deps, makeRelParts(callback)).f);
        } else if (!deps.splice) {
            //deps is a config object, not an array.
            config = deps;
            if (config.deps) {
                req(config.deps, config.callback);
            }
            if (!callback) {
                return;
            }

            if (callback.splice) {
                //callback is an array, which means it is a dependency list.
                //Adjust args if there are dependencies
                deps = callback;
                callback = relName;
                relName = null;
            } else {
                deps = undef;
            }
        }

        //Support require(['a'])
        callback = callback || function () {};

        //If relName is a function, it is an errback handler,
        //so remove it.
        if (typeof relName === 'function') {
            relName = forceSync;
            forceSync = alt;
        }

        //Simulate async callback;
        if (forceSync) {
            main(undef, deps, callback, relName);
        } else {
            //Using a non-zero value because of concern for what old browsers
            //do, and latest browsers "upgrade" to 4 if lower value is used:
            //http://www.whatwg.org/specs/web-apps/current-work/multipage/timers.html#dom-windowtimers-settimeout:
            //If want a value immediately, use require('id') instead -- something
            //that works in almond on the global level, but not guaranteed and
            //unlikely to work in other AMD implementations.
            setTimeout(function () {
                main(undef, deps, callback, relName);
            }, 4);
        }

        return req;
    };

    /**
     * Just drops the config on the floor, but returns req in case
     * the config return value is used.
     */
    req.config = function (cfg) {
        return req(cfg);
    };

    /**
     * Expose module registry for debugging and tooling
     */
    requirejs._defined = defined;

    define = function (name, deps, callback) {
        if (typeof name !== 'string') {
            throw new Error('See almond README: incorrect module build, no module name');
        }

        //This module may not have dependencies
        if (!deps.splice) {
            //deps is not an array, so probably means
            //an object literal or factory function for
            //the value. Adjust args.
            callback = deps;
            deps = [];
        }

        if (!hasProp(defined, name) && !hasProp(waiting, name)) {
            waiting[name] = [name, deps, callback];
        }
    };

    define.amd = {
        jQuery: true
    };
}());

define("../lib/almond.js", function(){});

(function(l){"object"===typeof exports?module.exports=l():"function"===typeof define&&define.amd?define('../lib/geostats.min',l):geostats=l()})(function(){var l=function(f){return"number"===typeof f&&parseFloat(f)==parseInt(f,10)&&!isNaN(f)};Array.prototype.indexOf||(Array.prototype.indexOf=function(f,a){if(void 0===this||null===this)throw new TypeError('"this" is null or not defined');var b=this.length>>>0;a=+a||0;Infinity===Math.abs(a)&&(a=0);0>a&&(a+=b,0>a&&(a=0));for(;a<b;a++)if(this[a]===f)return a;return-1});return function(f){this.objectID=
"";this.legendSeparator=this.separator=" - ";this.method="";this.precision=0;this.precisionflag="auto";this.roundlength=2;this.silent=this.debug=this.is_uniqueValues=!1;this.bounds=[];this.ranges=[];this.inner_ranges=null;this.colors=[];this.counter=[];this.stat_cov=this.stat_stddev=this.stat_variance=this.stat_pop=this.stat_min=this.stat_max=this.stat_sum=this.stat_median=this.stat_mean=this.stat_sorted=null;this.log=function(a,b){1!=this.debug&&null==b||console.log(this.objectID+"(object id) :: "+
a)};this.setBounds=function(a){this.log("Setting bounds ("+a.length+") : "+a.join());this.bounds=a};this.setSerie=function(a){this.log("Setting serie ("+a.length+") : "+a.join());this.serie=a;this.resetStatistics();this.setPrecision()};this.setColors=function(a){this.log("Setting color ramp ("+a.length+") : "+a.join());this.colors=a};this.doCount=function(){if(!this._nodata()){var a=this.sorted();this.counter=[];for(i=0;i<this.bounds.length-1;i++)this.counter[i]=0;for(j=0;j<a.length;j++){var b=this.getClass(a[j]);
this.counter[b]++}}};this.setPrecision=function(a){"undefined"!==typeof a&&(this.precisionflag="manual",this.precision=a);if("auto"==this.precisionflag)for(var b=0;b<this.serie.length;b++)a=isNaN(this.serie[b]+"")||-1==(this.serie[b]+"").toString().indexOf(".")?0:(this.serie[b]+"").split(".")[1].length,a>this.precision&&(this.precision=a);20<this.precision&&(this.log("this.precision value ("+this.precision+') is greater than max value. Automatic set-up to 20 to prevent "Uncaught RangeError: toFixed()" when calling decimalFormat() method.'),
this.precision=20);this.log("Calling setPrecision(). Mode : "+this.precisionflag+" - Decimals : "+this.precision);this.serie=this.decimalFormat(this.serie)};this.decimalFormat=function(a){for(var b=[],c=0;c<a.length;c++){var d=a[c];!isNaN(parseFloat(d))&&isFinite(d)?b[c]=parseFloat(parseFloat(a[c]).toFixed(this.precision)):b[c]=a[c]}return b};this.setRanges=function(){this.ranges=[];for(i=0;i<this.bounds.length-1;i++)this.ranges[i]=this.bounds[i]+this.separator+this.bounds[i+1]};this.min=function(){if(!this._nodata()){this.stat_min=
this.serie[0];for(i=0;i<this.pop();i++)this.serie[i]<this.stat_min&&(this.stat_min=this.serie[i]);return this.stat_min}};this.max=function(){if(!this._nodata()){this.stat_max=this.serie[0];for(i=0;i<this.pop();i++)this.serie[i]>this.stat_max&&(this.stat_max=this.serie[i]);return this.stat_max}};this.sum=function(){if(!this._nodata()){if(null==this.stat_sum)for(i=this.stat_sum=0;i<this.pop();i++)this.stat_sum+=parseFloat(this.serie[i]);return this.stat_sum}};this.pop=function(){if(!this._nodata())return null==
this.stat_pop&&(this.stat_pop=this.serie.length),this.stat_pop};this.mean=function(){if(!this._nodata())return null==this.stat_mean&&(this.stat_mean=parseFloat(this.sum()/this.pop())),this.stat_mean};this.median=function(){if(!this._nodata()){if(null==this.stat_median){this.stat_median=0;var a=this.sorted();this.stat_median=a.length%2?parseFloat(a[Math.ceil(a.length/2)-1]):(parseFloat(a[a.length/2-1])+parseFloat(a[a.length/2]))/2}return this.stat_median}};this.variance=function(){round="undefined"===
typeof round?!0:!1;if(!this._nodata()){if(null==this.stat_variance){for(var a=0,b=this.mean(),c=0;c<this.pop();c++)a+=Math.pow(this.serie[c]-b,2);this.stat_variance=a/this.pop();1==round&&(this.stat_variance=Math.round(this.stat_variance*Math.pow(10,this.roundlength))/Math.pow(10,this.roundlength))}return this.stat_variance}};this.stddev=function(a){a="undefined"===typeof a?!0:!1;if(!this._nodata())return null==this.stat_stddev&&(this.stat_stddev=Math.sqrt(this.variance()),1==a&&(this.stat_stddev=
Math.round(this.stat_stddev*Math.pow(10,this.roundlength))/Math.pow(10,this.roundlength))),this.stat_stddev};this.cov=function(a){a="undefined"===typeof a?!0:!1;if(!this._nodata())return null==this.stat_cov&&(this.stat_cov=this.stddev()/this.mean(),1==a&&(this.stat_cov=Math.round(this.stat_cov*Math.pow(10,this.roundlength))/Math.pow(10,this.roundlength))),this.stat_cov};this.resetStatistics=function(){this.stat_cov=this.stat_stddev=this.stat_variance=this.stat_pop=this.stat_min=this.stat_max=this.stat_sum=
this.stat_median=this.stat_mean=this.stat_sorted=null};this._nodata=function(){return 0==this.serie.length?(this.silent?this.log("[silent mode] Error. You should first enter a serie!",!0):alert("Error. You should first enter a serie!"),1):0};this._hasNegativeValue=function(){for(i=0;i<this.serie.length;i++)if(0>this.serie[i])return!0;return!1};this._hasZeroValue=function(){for(i=0;i<this.serie.length;i++)if(0===parseFloat(this.serie[i]))return!0;return!1};this.sorted=function(){null==this.stat_sorted&&
(this.stat_sorted=0==this.is_uniqueValues?this.serie.sort(function(a,b){return a-b}):this.serie.sort(function(a,b){var c=a.toString().toLowerCase(),d=b.toString().toLowerCase();return c<d?-1:c>d?1:0}));return this.stat_sorted};this.info=function(){if(!this._nodata()){var a=""+("Population : "+this.pop()+" - [Min : "+this.min()+" | Max : "+this.max()+"]\n");a+="Mean : "+this.mean()+" - Median : "+this.median()+"\n";return a+="Variance : "+this.variance()+" - Standard deviation : "+this.stddev()+" - Coefficient of variation : "+
this.cov()+"\n"}};this.setClassManually=function(a){if(!this._nodata())if(a[0]!==this.min()||a[a.length-1]!==this.max())this.silent?this.log("[silent mode] "+t("Given bounds may not be correct! please check your input.\nMin value : "+this.min()+" / Max value : "+this.max()),!0):alert("Given bounds may not be correct! please check your input.\nMin value : "+this.min()+" / Max value : "+this.max());else return this.setBounds(a),this.setRanges(),this.method="manual classification ("+(a.length-1)+" classes)",
this.bounds};this.getClassEqInterval=function(a,b,c){if(!this._nodata()){b="undefined"===typeof b?this.min():b;c="undefined"===typeof c?this.max():c;var d=[],e=b;b=(c-b)/a;for(i=0;i<=a;i++)d[i]=e,e+=b;d[a]=c;this.setBounds(d);this.setRanges();this.method="eq. intervals ("+a+" classes)";return this.bounds}};this.getQuantiles=function(a){for(var b=this.sorted(),c=[],d=this.pop()/a,e=1;e<a;e++)c.push(b[Math.round(e*d+.49)-1]);return c};this.getClassQuantile=function(a){if(!this._nodata()){var b=this.sorted(),
c=this.getQuantiles(a);c.unshift(b[0]);c[b.length-1]!==b[b.length-1]&&c.push(b[b.length-1]);this.setBounds(c);this.setRanges();this.method="quantile ("+a+" classes)";return this.bounds}};this.getClassStdDeviation=function(a,b){if(!this._nodata()){this.max();this.min();var c=[];if(1==a%2){var d=Math.floor(a/2);var e=d+1;c[d]=this.mean()-this.stddev()/2;c[e]=this.mean()+this.stddev()/2;i=d-1}else e=a/2,c[e]=this.mean(),i=e-1;for(;0<i;i--)d=c[i+1]-this.stddev(),c[i]=d;for(i=e+1;i<a;i++)d=c[i-1]+this.stddev(),
c[i]=d;c[0]="undefined"===typeof b?c[1]-this.stddev():this.min();c[a]="undefined"===typeof b?c[a-1]+this.stddev():this.max();this.setBounds(c);this.setRanges();this.method="std deviation ("+a+" classes)";return this.bounds}};this.getClassGeometricProgression=function(a){if(!this._nodata())if(this._hasNegativeValue()||this._hasZeroValue())this.silent?this.log("[silent mode] geometric progression can't be applied with a serie containing negative or zero values.",!0):alert("geometric progression can't be applied with a serie containing negative or zero values.");
else{var b=[],c=this.min(),d=this.max(),c=Math.log(c)/Math.LN10,d=(Math.log(d)/Math.LN10-c)/a;for(i=0;i<a;i++)b[i]=0==i?c:b[i-1]+d;b=b.map(function(a){return Math.pow(10,a)});b.push(this.max());this.setBounds(b);this.setRanges();this.method="geometric progression ("+a+" classes)";return this.bounds}};this.getClassArithmeticProgression=function(a){if(!this._nodata()){var b=0;for(i=1;i<=a;i++)b+=i;var c=[],d=this.min(),b=(this.max()-d)/b;for(i=0;i<=a;i++)c[i]=0==i?d:c[i-1]+i*b;this.setBounds(c);this.setRanges();
this.method="arithmetic progression ("+a+" classes)";return this.bounds}};this.getClassJenks=function(a){if(!this._nodata()){dataList=this.sorted();for(var b=[],c=0,d=dataList.length+1;c<d;c++){for(var e=[],g=0,f=a+1;g<f;g++)e.push(0);b.push(e)}c=[];d=0;for(e=dataList.length+1;d<e;d++){for(var g=[],f=0,k=a+1;f<k;f++)g.push(0);c.push(g)}d=1;for(e=a+1;d<e;d++){b[0][d]=1;c[0][d]=0;var h=1;for(g=dataList.length+1;h<g;h++)c[h][d]=Infinity;h=0}d=2;for(e=dataList.length+1;d<e;d++){for(var k=f=g=0,l=1,q=
d+1;l<q;l++){var n=d-l+1;h=parseFloat(dataList[n-1]);f+=h*h;g+=h;k+=1;h=f-g*g/k;var p=n-1;if(0!=p)for(var m=2,r=a+1;m<r;m++)c[d][m]>=h+c[p][m-1]&&(b[d][m]=n,c[d][m]=h+c[p][m-1])}b[d][1]=1;c[d][1]=h}h=dataList.length;c=[];for(d=0;d<=a;d++)c.push(0);c[a]=parseFloat(dataList[dataList.length-1]);c[0]=parseFloat(dataList[0]);for(d=a;2<=d;)e=parseInt(b[h][d]-2),c[d-1]=dataList[e],h=parseInt(b[h][d]-1),--d;c[0]==c[1]&&(c[0]=0);this.setBounds(c);this.setRanges();this.method="Jenks ("+a+" classes)";return this.bounds}};
this.getClassUniqueValues=function(){if(!this._nodata()){this.is_uniqueValues=!0;var a=this.sorted(),b=[];for(i=0;i<this.pop();i++)-1===b.indexOf(a[i])&&b.push(a[i]);this.bounds=b;this.method="unique values";return b}};this.getClass=function(a){for(i=0;i<this.bounds.length;i++)if(1==this.is_uniqueValues){if(a==this.bounds[i])return i}else if(parseFloat(a)<=this.bounds[i+1])return i;return"Unable to get value's class."};this.getRanges=function(){return this.ranges};this.getRangeNum=function(a){var b;
for(b=0;b<this.ranges.length;b++){var c=this.ranges[b].split(/ - /);if(a<=parseFloat(c[1]))return b}};this.getInnerRanges=function(){var a;if(null!=this.inner_ranges)return this.inner_ranges;var b=[],c=this.sorted(),d=1;for(i=0;i<c.length;i++)if(0==i&&(a=c[i]),parseFloat(c[i])>parseFloat(this.bounds[d])&&(b[d-1]=""+a+this.separator+c[i-1],a=c[i],d++),d==this.bounds.length-1)return b[d-1]=""+a+this.separator+c[c.length-1],this.inner_ranges=b};this.getSortedlist=function(){return this.sorted().join(", ")};
this.getHtmlLegend=function(a,b,c,d,e,f){var g="",k=[];this.doCount();ccolors=null!=a?a:this.colors;lg=null!=b?b:"Legend";getcounter=null!=c?!0:!1;fn=null!=d?d:function(a){return a};null==e&&(e="default");if("discontinuous"==e&&(this.getInnerRanges(),-1!==this.counter.indexOf(0))){this.silent?this.log("[silent mode] Geostats cannot apply 'discontinuous' mode to the getHtmlLegend() method because some classes are not populated.\nPlease switch to 'default' or 'distinct' modes. Exit!",!0):alert("Geostats cannot apply 'discontinuous' mode to the getHtmlLegend() method because some classes are not populated.\nPlease switch to 'default' or 'distinct' modes. Exit!");
return}"DESC"!==f&&(f="ASC");if(ccolors.length<this.ranges.length)this.silent?this.log("[silent mode] The number of colors should fit the number of ranges. Exit!",!0):alert("The number of colors should fit the number of ranges. Exit!");else{if(0==this.is_uniqueValues)for(i=0;i<this.ranges.length;i++)!0===getcounter&&(g=' <span class="geostats-legend-counter">('+this.counter[i]+")</span>"),b=this.ranges[i].split(this.separator),a=parseFloat(b[0]).toFixed(this.precision),b=parseFloat(b[1]).toFixed(this.precision),
"distinct"==e&&0!=i&&(l(a)?(a=parseInt(a)+1,"manual"==this.precisionflag&&0!=this.precision&&(a=parseFloat(a).toFixed(this.precision))):(a=parseFloat(a)+1/Math.pow(10,this.precision),a=parseFloat(a).toFixed(this.precision))),"discontinuous"==e&&(b=this.inner_ranges[i].split(this.separator),a=parseFloat(b[0]).toFixed(this.precision),b=parseFloat(b[1]).toFixed(this.precision)),a=fn(a)+this.legendSeparator+fn(b),a='<div><div class="geostats-legend-block" style="background-color:'+ccolors[i]+'"></div> '+
a+g+"</div>",k.push(a);else for(i=0;i<this.bounds.length;i++)!0===getcounter&&(g=' <span class="geostats-legend-counter">('+this.counter[i]+")</span>"),a=fn(this.bounds[i]),a='<div><div class="geostats-legend-block" style="background-color:'+ccolors[i]+'"></div> '+a+g+"</div>",k.push(a);"DESC"===f&&k.reverse();e='<div class="geostats-legend"><div class="geostats-legend-title">'+lg+"</div>";for(i=0;i<k.length;i++)e+=k[i];return e+"</div>"}};this.objectID=(new Date).getUTCMilliseconds();this.log("Creating new geostats object");
"undefined"!==typeof f&&0<f.length?(this.serie=f,this.setPrecision(),this.log("Setting serie ("+f.length+") : "+f.join())):this.serie=[];this.getJenks=this.getClassJenks;this.getGeometricProgression=this.getClassGeometricProgression;this.getEqInterval=this.getClassEqInterval;this.getQuantile=this.getClassQuantile;this.getStdDeviation=this.getClassStdDeviation;this.getUniqueValues=this.getClassUniqueValues;this.getArithmeticProgression=this.getClassArithmeticProgression}});

define('../lib/colorbrewer.v1.min',[],function(){
var colorbrewer={YlGn:{3:["#f7fcb9","#addd8e","#31a354"],4:["#ffffcc","#c2e699","#78c679","#238443"],5:["#ffffcc","#c2e699","#78c679","#31a354","#006837"],6:["#ffffcc","#d9f0a3","#addd8e","#78c679","#31a354","#006837"],7:["#ffffcc","#d9f0a3","#addd8e","#78c679","#41ab5d","#238443","#005a32"],8:["#ffffe5","#f7fcb9","#d9f0a3","#addd8e","#78c679","#41ab5d","#238443","#005a32"],9:["#ffffe5","#f7fcb9","#d9f0a3","#addd8e","#78c679","#41ab5d","#238443","#006837","#004529"]},YlGnBu:{3:["#edf8b1","#7fcdbb","#2c7fb8"],4:["#ffffcc","#a1dab4","#41b6c4","#225ea8"],5:["#ffffcc","#a1dab4","#41b6c4","#2c7fb8","#253494"],6:["#ffffcc","#c7e9b4","#7fcdbb","#41b6c4","#2c7fb8","#253494"],7:["#ffffcc","#c7e9b4","#7fcdbb","#41b6c4","#1d91c0","#225ea8","#0c2c84"],8:["#ffffd9","#edf8b1","#c7e9b4","#7fcdbb","#41b6c4","#1d91c0","#225ea8","#0c2c84"],9:["#ffffd9","#edf8b1","#c7e9b4","#7fcdbb","#41b6c4","#1d91c0","#225ea8","#253494","#081d58"]},GnBu:{3:["#e0f3db","#a8ddb5","#43a2ca"],4:["#f0f9e8","#bae4bc","#7bccc4","#2b8cbe"],5:["#f0f9e8","#bae4bc","#7bccc4","#43a2ca","#0868ac"],6:["#f0f9e8","#ccebc5","#a8ddb5","#7bccc4","#43a2ca","#0868ac"],7:["#f0f9e8","#ccebc5","#a8ddb5","#7bccc4","#4eb3d3","#2b8cbe","#08589e"],8:["#f7fcf0","#e0f3db","#ccebc5","#a8ddb5","#7bccc4","#4eb3d3","#2b8cbe","#08589e"],9:["#f7fcf0","#e0f3db","#ccebc5","#a8ddb5","#7bccc4","#4eb3d3","#2b8cbe","#0868ac","#084081"]},BuGn:{3:["#e5f5f9","#99d8c9","#2ca25f"],4:["#edf8fb","#b2e2e2","#66c2a4","#238b45"],5:["#edf8fb","#b2e2e2","#66c2a4","#2ca25f","#006d2c"],6:["#edf8fb","#ccece6","#99d8c9","#66c2a4","#2ca25f","#006d2c"],7:["#edf8fb","#ccece6","#99d8c9","#66c2a4","#41ae76","#238b45","#005824"],8:["#f7fcfd","#e5f5f9","#ccece6","#99d8c9","#66c2a4","#41ae76","#238b45","#005824"],9:["#f7fcfd","#e5f5f9","#ccece6","#99d8c9","#66c2a4","#41ae76","#238b45","#006d2c","#00441b"]},PuBuGn:{3:["#ece2f0","#a6bddb","#1c9099"],4:["#f6eff7","#bdc9e1","#67a9cf","#02818a"],5:["#f6eff7","#bdc9e1","#67a9cf","#1c9099","#016c59"],6:["#f6eff7","#d0d1e6","#a6bddb","#67a9cf","#1c9099","#016c59"],7:["#f6eff7","#d0d1e6","#a6bddb","#67a9cf","#3690c0","#02818a","#016450"],8:["#fff7fb","#ece2f0","#d0d1e6","#a6bddb","#67a9cf","#3690c0","#02818a","#016450"],9:["#fff7fb","#ece2f0","#d0d1e6","#a6bddb","#67a9cf","#3690c0","#02818a","#016c59","#014636"]},PuBu:{3:["#ece7f2","#a6bddb","#2b8cbe"],4:["#f1eef6","#bdc9e1","#74a9cf","#0570b0"],5:["#f1eef6","#bdc9e1","#74a9cf","#2b8cbe","#045a8d"],6:["#f1eef6","#d0d1e6","#a6bddb","#74a9cf","#2b8cbe","#045a8d"],7:["#f1eef6","#d0d1e6","#a6bddb","#74a9cf","#3690c0","#0570b0","#034e7b"],8:["#fff7fb","#ece7f2","#d0d1e6","#a6bddb","#74a9cf","#3690c0","#0570b0","#034e7b"],9:["#fff7fb","#ece7f2","#d0d1e6","#a6bddb","#74a9cf","#3690c0","#0570b0","#045a8d","#023858"]},BuPu:{3:["#e0ecf4","#9ebcda","#8856a7"],4:["#edf8fb","#b3cde3","#8c96c6","#88419d"],5:["#edf8fb","#b3cde3","#8c96c6","#8856a7","#810f7c"],6:["#edf8fb","#bfd3e6","#9ebcda","#8c96c6","#8856a7","#810f7c"],7:["#edf8fb","#bfd3e6","#9ebcda","#8c96c6","#8c6bb1","#88419d","#6e016b"],8:["#f7fcfd","#e0ecf4","#bfd3e6","#9ebcda","#8c96c6","#8c6bb1","#88419d","#6e016b"],9:["#f7fcfd","#e0ecf4","#bfd3e6","#9ebcda","#8c96c6","#8c6bb1","#88419d","#810f7c","#4d004b"]},RdPu:{3:["#fde0dd","#fa9fb5","#c51b8a"],4:["#feebe2","#fbb4b9","#f768a1","#ae017e"],5:["#feebe2","#fbb4b9","#f768a1","#c51b8a","#7a0177"],6:["#feebe2","#fcc5c0","#fa9fb5","#f768a1","#c51b8a","#7a0177"],7:["#feebe2","#fcc5c0","#fa9fb5","#f768a1","#dd3497","#ae017e","#7a0177"],8:["#fff7f3","#fde0dd","#fcc5c0","#fa9fb5","#f768a1","#dd3497","#ae017e","#7a0177"],9:["#fff7f3","#fde0dd","#fcc5c0","#fa9fb5","#f768a1","#dd3497","#ae017e","#7a0177","#49006a"]},PuRd:{3:["#e7e1ef","#c994c7","#dd1c77"],4:["#f1eef6","#d7b5d8","#df65b0","#ce1256"],5:["#f1eef6","#d7b5d8","#df65b0","#dd1c77","#980043"],6:["#f1eef6","#d4b9da","#c994c7","#df65b0","#dd1c77","#980043"],7:["#f1eef6","#d4b9da","#c994c7","#df65b0","#e7298a","#ce1256","#91003f"],8:["#f7f4f9","#e7e1ef","#d4b9da","#c994c7","#df65b0","#e7298a","#ce1256","#91003f"],9:["#f7f4f9","#e7e1ef","#d4b9da","#c994c7","#df65b0","#e7298a","#ce1256","#980043","#67001f"]},OrRd:{3:["#fee8c8","#fdbb84","#e34a33"],4:["#fef0d9","#fdcc8a","#fc8d59","#d7301f"],5:["#fef0d9","#fdcc8a","#fc8d59","#e34a33","#b30000"],6:["#fef0d9","#fdd49e","#fdbb84","#fc8d59","#e34a33","#b30000"],7:["#fef0d9","#fdd49e","#fdbb84","#fc8d59","#ef6548","#d7301f","#990000"],8:["#fff7ec","#fee8c8","#fdd49e","#fdbb84","#fc8d59","#ef6548","#d7301f","#990000"],9:["#fff7ec","#fee8c8","#fdd49e","#fdbb84","#fc8d59","#ef6548","#d7301f","#b30000","#7f0000"]},YlOrRd:{3:["#ffeda0","#feb24c","#f03b20"],4:["#ffffb2","#fecc5c","#fd8d3c","#e31a1c"],5:["#ffffb2","#fecc5c","#fd8d3c","#f03b20","#bd0026"],6:["#ffffb2","#fed976","#feb24c","#fd8d3c","#f03b20","#bd0026"],7:["#ffffb2","#fed976","#feb24c","#fd8d3c","#fc4e2a","#e31a1c","#b10026"],8:["#ffffcc","#ffeda0","#fed976","#feb24c","#fd8d3c","#fc4e2a","#e31a1c","#b10026"],9:["#ffffcc","#ffeda0","#fed976","#feb24c","#fd8d3c","#fc4e2a","#e31a1c","#bd0026","#800026"]},YlOrBr:{3:["#fff7bc","#fec44f","#d95f0e"],4:["#ffffd4","#fed98e","#fe9929","#cc4c02"],5:["#ffffd4","#fed98e","#fe9929","#d95f0e","#993404"],6:["#ffffd4","#fee391","#fec44f","#fe9929","#d95f0e","#993404"],7:["#ffffd4","#fee391","#fec44f","#fe9929","#ec7014","#cc4c02","#8c2d04"],8:["#ffffe5","#fff7bc","#fee391","#fec44f","#fe9929","#ec7014","#cc4c02","#8c2d04"],9:["#ffffe5","#fff7bc","#fee391","#fec44f","#fe9929","#ec7014","#cc4c02","#993404","#662506"]},Purples:{3:["#efedf5","#bcbddc","#756bb1"],4:["#f2f0f7","#cbc9e2","#9e9ac8","#6a51a3"],5:["#f2f0f7","#cbc9e2","#9e9ac8","#756bb1","#54278f"],6:["#f2f0f7","#dadaeb","#bcbddc","#9e9ac8","#756bb1","#54278f"],7:["#f2f0f7","#dadaeb","#bcbddc","#9e9ac8","#807dba","#6a51a3","#4a1486"],8:["#fcfbfd","#efedf5","#dadaeb","#bcbddc","#9e9ac8","#807dba","#6a51a3","#4a1486"],9:["#fcfbfd","#efedf5","#dadaeb","#bcbddc","#9e9ac8","#807dba","#6a51a3","#54278f","#3f007d"]},Blues:{3:["#deebf7","#9ecae1","#3182bd"],4:["#eff3ff","#bdd7e7","#6baed6","#2171b5"],5:["#eff3ff","#bdd7e7","#6baed6","#3182bd","#08519c"],6:["#eff3ff","#c6dbef","#9ecae1","#6baed6","#3182bd","#08519c"],7:["#eff3ff","#c6dbef","#9ecae1","#6baed6","#4292c6","#2171b5","#084594"],8:["#f7fbff","#deebf7","#c6dbef","#9ecae1","#6baed6","#4292c6","#2171b5","#084594"],9:["#f7fbff","#deebf7","#c6dbef","#9ecae1","#6baed6","#4292c6","#2171b5","#08519c","#08306b"]},Greens:{3:["#e5f5e0","#a1d99b","#31a354"],4:["#edf8e9","#bae4b3","#74c476","#238b45"],5:["#edf8e9","#bae4b3","#74c476","#31a354","#006d2c"],6:["#edf8e9","#c7e9c0","#a1d99b","#74c476","#31a354","#006d2c"],7:["#edf8e9","#c7e9c0","#a1d99b","#74c476","#41ab5d","#238b45","#005a32"],8:["#f7fcf5","#e5f5e0","#c7e9c0","#a1d99b","#74c476","#41ab5d","#238b45","#005a32"],9:["#f7fcf5","#e5f5e0","#c7e9c0","#a1d99b","#74c476","#41ab5d","#238b45","#006d2c","#00441b"]},Oranges:{3:["#fee6ce","#fdae6b","#e6550d"],4:["#feedde","#fdbe85","#fd8d3c","#d94701"],5:["#feedde","#fdbe85","#fd8d3c","#e6550d","#a63603"],6:["#feedde","#fdd0a2","#fdae6b","#fd8d3c","#e6550d","#a63603"],7:["#feedde","#fdd0a2","#fdae6b","#fd8d3c","#f16913","#d94801","#8c2d04"],8:["#fff5eb","#fee6ce","#fdd0a2","#fdae6b","#fd8d3c","#f16913","#d94801","#8c2d04"],9:["#fff5eb","#fee6ce","#fdd0a2","#fdae6b","#fd8d3c","#f16913","#d94801","#a63603","#7f2704"]},Reds:{3:["#fee0d2","#fc9272","#de2d26"],4:["#fee5d9","#fcae91","#fb6a4a","#cb181d"],5:["#fee5d9","#fcae91","#fb6a4a","#de2d26","#a50f15"],6:["#fee5d9","#fcbba1","#fc9272","#fb6a4a","#de2d26","#a50f15"],7:["#fee5d9","#fcbba1","#fc9272","#fb6a4a","#ef3b2c","#cb181d","#99000d"],8:["#fff5f0","#fee0d2","#fcbba1","#fc9272","#fb6a4a","#ef3b2c","#cb181d","#99000d"],9:["#fff5f0","#fee0d2","#fcbba1","#fc9272","#fb6a4a","#ef3b2c","#cb181d","#a50f15","#67000d"]},Greys:{3:["#f0f0f0","#bdbdbd","#636363"],4:["#f7f7f7","#cccccc","#969696","#525252"],5:["#f7f7f7","#cccccc","#969696","#636363","#252525"],6:["#f7f7f7","#d9d9d9","#bdbdbd","#969696","#636363","#252525"],7:["#f7f7f7","#d9d9d9","#bdbdbd","#969696","#737373","#525252","#252525"],8:["#ffffff","#f0f0f0","#d9d9d9","#bdbdbd","#969696","#737373","#525252","#252525"],9:["#ffffff","#f0f0f0","#d9d9d9","#bdbdbd","#969696","#737373","#525252","#252525","#000000"]},PuOr:{3:["#f1a340","#f7f7f7","#998ec3"],4:["#e66101","#fdb863","#b2abd2","#5e3c99"],5:["#e66101","#fdb863","#f7f7f7","#b2abd2","#5e3c99"],6:["#b35806","#f1a340","#fee0b6","#d8daeb","#998ec3","#542788"],7:["#b35806","#f1a340","#fee0b6","#f7f7f7","#d8daeb","#998ec3","#542788"],8:["#b35806","#e08214","#fdb863","#fee0b6","#d8daeb","#b2abd2","#8073ac","#542788"],9:["#b35806","#e08214","#fdb863","#fee0b6","#f7f7f7","#d8daeb","#b2abd2","#8073ac","#542788"],10:["#7f3b08","#b35806","#e08214","#fdb863","#fee0b6","#d8daeb","#b2abd2","#8073ac","#542788","#2d004b"],11:["#7f3b08","#b35806","#e08214","#fdb863","#fee0b6","#f7f7f7","#d8daeb","#b2abd2","#8073ac","#542788","#2d004b"]},BrBG:{3:["#d8b365","#f5f5f5","#5ab4ac"],4:["#a6611a","#dfc27d","#80cdc1","#018571"],5:["#a6611a","#dfc27d","#f5f5f5","#80cdc1","#018571"],6:["#8c510a","#d8b365","#f6e8c3","#c7eae5","#5ab4ac","#01665e"],7:["#8c510a","#d8b365","#f6e8c3","#f5f5f5","#c7eae5","#5ab4ac","#01665e"],8:["#8c510a","#bf812d","#dfc27d","#f6e8c3","#c7eae5","#80cdc1","#35978f","#01665e"],9:["#8c510a","#bf812d","#dfc27d","#f6e8c3","#f5f5f5","#c7eae5","#80cdc1","#35978f","#01665e"],10:["#543005","#8c510a","#bf812d","#dfc27d","#f6e8c3","#c7eae5","#80cdc1","#35978f","#01665e","#003c30"],11:["#543005","#8c510a","#bf812d","#dfc27d","#f6e8c3","#f5f5f5","#c7eae5","#80cdc1","#35978f","#01665e","#003c30"]},PRGn:{3:["#af8dc3","#f7f7f7","#7fbf7b"],4:["#7b3294","#c2a5cf","#a6dba0","#008837"],5:["#7b3294","#c2a5cf","#f7f7f7","#a6dba0","#008837"],6:["#762a83","#af8dc3","#e7d4e8","#d9f0d3","#7fbf7b","#1b7837"],7:["#762a83","#af8dc3","#e7d4e8","#f7f7f7","#d9f0d3","#7fbf7b","#1b7837"],8:["#762a83","#9970ab","#c2a5cf","#e7d4e8","#d9f0d3","#a6dba0","#5aae61","#1b7837"],9:["#762a83","#9970ab","#c2a5cf","#e7d4e8","#f7f7f7","#d9f0d3","#a6dba0","#5aae61","#1b7837"],10:["#40004b","#762a83","#9970ab","#c2a5cf","#e7d4e8","#d9f0d3","#a6dba0","#5aae61","#1b7837","#00441b"],11:["#40004b","#762a83","#9970ab","#c2a5cf","#e7d4e8","#f7f7f7","#d9f0d3","#a6dba0","#5aae61","#1b7837","#00441b"]},PiYG:{3:["#e9a3c9","#f7f7f7","#a1d76a"],4:["#d01c8b","#f1b6da","#b8e186","#4dac26"],5:["#d01c8b","#f1b6da","#f7f7f7","#b8e186","#4dac26"],6:["#c51b7d","#e9a3c9","#fde0ef","#e6f5d0","#a1d76a","#4d9221"],7:["#c51b7d","#e9a3c9","#fde0ef","#f7f7f7","#e6f5d0","#a1d76a","#4d9221"],8:["#c51b7d","#de77ae","#f1b6da","#fde0ef","#e6f5d0","#b8e186","#7fbc41","#4d9221"],9:["#c51b7d","#de77ae","#f1b6da","#fde0ef","#f7f7f7","#e6f5d0","#b8e186","#7fbc41","#4d9221"],10:["#8e0152","#c51b7d","#de77ae","#f1b6da","#fde0ef","#e6f5d0","#b8e186","#7fbc41","#4d9221","#276419"],11:["#8e0152","#c51b7d","#de77ae","#f1b6da","#fde0ef","#f7f7f7","#e6f5d0","#b8e186","#7fbc41","#4d9221","#276419"]},RdBu:{3:["#ef8a62","#f7f7f7","#67a9cf"],4:["#ca0020","#f4a582","#92c5de","#0571b0"],5:["#ca0020","#f4a582","#f7f7f7","#92c5de","#0571b0"],6:["#b2182b","#ef8a62","#fddbc7","#d1e5f0","#67a9cf","#2166ac"],7:["#b2182b","#ef8a62","#fddbc7","#f7f7f7","#d1e5f0","#67a9cf","#2166ac"],8:["#b2182b","#d6604d","#f4a582","#fddbc7","#d1e5f0","#92c5de","#4393c3","#2166ac"],9:["#b2182b","#d6604d","#f4a582","#fddbc7","#f7f7f7","#d1e5f0","#92c5de","#4393c3","#2166ac"],10:["#67001f","#b2182b","#d6604d","#f4a582","#fddbc7","#d1e5f0","#92c5de","#4393c3","#2166ac","#053061"],11:["#67001f","#b2182b","#d6604d","#f4a582","#fddbc7","#f7f7f7","#d1e5f0","#92c5de","#4393c3","#2166ac","#053061"]},RdGy:{3:["#ef8a62","#ffffff","#999999"],4:["#ca0020","#f4a582","#bababa","#404040"],5:["#ca0020","#f4a582","#ffffff","#bababa","#404040"],6:["#b2182b","#ef8a62","#fddbc7","#e0e0e0","#999999","#4d4d4d"],7:["#b2182b","#ef8a62","#fddbc7","#ffffff","#e0e0e0","#999999","#4d4d4d"],8:["#b2182b","#d6604d","#f4a582","#fddbc7","#e0e0e0","#bababa","#878787","#4d4d4d"],9:["#b2182b","#d6604d","#f4a582","#fddbc7","#ffffff","#e0e0e0","#bababa","#878787","#4d4d4d"],10:["#67001f","#b2182b","#d6604d","#f4a582","#fddbc7","#e0e0e0","#bababa","#878787","#4d4d4d","#1a1a1a"],11:["#67001f","#b2182b","#d6604d","#f4a582","#fddbc7","#ffffff","#e0e0e0","#bababa","#878787","#4d4d4d","#1a1a1a"]},RdYlBu:{3:["#fc8d59","#ffffbf","#91bfdb"],4:["#d7191c","#fdae61","#abd9e9","#2c7bb6"],5:["#d7191c","#fdae61","#ffffbf","#abd9e9","#2c7bb6"],6:["#d73027","#fc8d59","#fee090","#e0f3f8","#91bfdb","#4575b4"],7:["#d73027","#fc8d59","#fee090","#ffffbf","#e0f3f8","#91bfdb","#4575b4"],8:["#d73027","#f46d43","#fdae61","#fee090","#e0f3f8","#abd9e9","#74add1","#4575b4"],9:["#d73027","#f46d43","#fdae61","#fee090","#ffffbf","#e0f3f8","#abd9e9","#74add1","#4575b4"],10:["#a50026","#d73027","#f46d43","#fdae61","#fee090","#e0f3f8","#abd9e9","#74add1","#4575b4","#313695"],11:["#a50026","#d73027","#f46d43","#fdae61","#fee090","#ffffbf","#e0f3f8","#abd9e9","#74add1","#4575b4","#313695"]},Spectral:{3:["#fc8d59","#ffffbf","#99d594"],4:["#d7191c","#fdae61","#abdda4","#2b83ba"],5:["#d7191c","#fdae61","#ffffbf","#abdda4","#2b83ba"],6:["#d53e4f","#fc8d59","#fee08b","#e6f598","#99d594","#3288bd"],7:["#d53e4f","#fc8d59","#fee08b","#ffffbf","#e6f598","#99d594","#3288bd"],8:["#d53e4f","#f46d43","#fdae61","#fee08b","#e6f598","#abdda4","#66c2a5","#3288bd"],9:["#d53e4f","#f46d43","#fdae61","#fee08b","#ffffbf","#e6f598","#abdda4","#66c2a5","#3288bd"],10:["#9e0142","#d53e4f","#f46d43","#fdae61","#fee08b","#e6f598","#abdda4","#66c2a5","#3288bd","#5e4fa2"],11:["#9e0142","#d53e4f","#f46d43","#fdae61","#fee08b","#ffffbf","#e6f598","#abdda4","#66c2a5","#3288bd","#5e4fa2"]},RdYlGn:{3:["#fc8d59","#ffffbf","#91cf60"],4:["#d7191c","#fdae61","#a6d96a","#1a9641"],5:["#d7191c","#fdae61","#ffffbf","#a6d96a","#1a9641"],6:["#d73027","#fc8d59","#fee08b","#d9ef8b","#91cf60","#1a9850"],7:["#d73027","#fc8d59","#fee08b","#ffffbf","#d9ef8b","#91cf60","#1a9850"],8:["#d73027","#f46d43","#fdae61","#fee08b","#d9ef8b","#a6d96a","#66bd63","#1a9850"],9:["#d73027","#f46d43","#fdae61","#fee08b","#ffffbf","#d9ef8b","#a6d96a","#66bd63","#1a9850"],10:["#a50026","#d73027","#f46d43","#fdae61","#fee08b","#d9ef8b","#a6d96a","#66bd63","#1a9850","#006837"],11:["#a50026","#d73027","#f46d43","#fdae61","#fee08b","#ffffbf","#d9ef8b","#a6d96a","#66bd63","#1a9850","#006837"]},Accent:{3:["#7fc97f","#beaed4","#fdc086"],4:["#7fc97f","#beaed4","#fdc086","#ffff99"],5:["#7fc97f","#beaed4","#fdc086","#ffff99","#386cb0"],6:["#7fc97f","#beaed4","#fdc086","#ffff99","#386cb0","#f0027f"],7:["#7fc97f","#beaed4","#fdc086","#ffff99","#386cb0","#f0027f","#bf5b17"],8:["#7fc97f","#beaed4","#fdc086","#ffff99","#386cb0","#f0027f","#bf5b17","#666666"]},Dark2:{3:["#1b9e77","#d95f02","#7570b3"],4:["#1b9e77","#d95f02","#7570b3","#e7298a"],5:["#1b9e77","#d95f02","#7570b3","#e7298a","#66a61e"],6:["#1b9e77","#d95f02","#7570b3","#e7298a","#66a61e","#e6ab02"],7:["#1b9e77","#d95f02","#7570b3","#e7298a","#66a61e","#e6ab02","#a6761d"],8:["#1b9e77","#d95f02","#7570b3","#e7298a","#66a61e","#e6ab02","#a6761d","#666666"]},Paired:{3:["#a6cee3","#1f78b4","#b2df8a"],4:["#a6cee3","#1f78b4","#b2df8a","#33a02c"],5:["#a6cee3","#1f78b4","#b2df8a","#33a02c","#fb9a99"],6:["#a6cee3","#1f78b4","#b2df8a","#33a02c","#fb9a99","#e31a1c"],7:["#a6cee3","#1f78b4","#b2df8a","#33a02c","#fb9a99","#e31a1c","#fdbf6f"],8:["#a6cee3","#1f78b4","#b2df8a","#33a02c","#fb9a99","#e31a1c","#fdbf6f","#ff7f00"],9:["#a6cee3","#1f78b4","#b2df8a","#33a02c","#fb9a99","#e31a1c","#fdbf6f","#ff7f00","#cab2d6"],10:["#a6cee3","#1f78b4","#b2df8a","#33a02c","#fb9a99","#e31a1c","#fdbf6f","#ff7f00","#cab2d6","#6a3d9a"],11:["#a6cee3","#1f78b4","#b2df8a","#33a02c","#fb9a99","#e31a1c","#fdbf6f","#ff7f00","#cab2d6","#6a3d9a","#ffff99"],12:["#a6cee3","#1f78b4","#b2df8a","#33a02c","#fb9a99","#e31a1c","#fdbf6f","#ff7f00","#cab2d6","#6a3d9a","#ffff99","#b15928"]},Pastel1:{3:["#fbb4ae","#b3cde3","#ccebc5"],4:["#fbb4ae","#b3cde3","#ccebc5","#decbe4"],5:["#fbb4ae","#b3cde3","#ccebc5","#decbe4","#fed9a6"],6:["#fbb4ae","#b3cde3","#ccebc5","#decbe4","#fed9a6","#ffffcc"],7:["#fbb4ae","#b3cde3","#ccebc5","#decbe4","#fed9a6","#ffffcc","#e5d8bd"],8:["#fbb4ae","#b3cde3","#ccebc5","#decbe4","#fed9a6","#ffffcc","#e5d8bd","#fddaec"],9:["#fbb4ae","#b3cde3","#ccebc5","#decbe4","#fed9a6","#ffffcc","#e5d8bd","#fddaec","#f2f2f2"]},Pastel2:{3:["#b3e2cd","#fdcdac","#cbd5e8"],4:["#b3e2cd","#fdcdac","#cbd5e8","#f4cae4"],5:["#b3e2cd","#fdcdac","#cbd5e8","#f4cae4","#e6f5c9"],6:["#b3e2cd","#fdcdac","#cbd5e8","#f4cae4","#e6f5c9","#fff2ae"],7:["#b3e2cd","#fdcdac","#cbd5e8","#f4cae4","#e6f5c9","#fff2ae","#f1e2cc"],8:["#b3e2cd","#fdcdac","#cbd5e8","#f4cae4","#e6f5c9","#fff2ae","#f1e2cc","#cccccc"]},Set1:{3:["#e41a1c","#377eb8","#4daf4a"],4:["#e41a1c","#377eb8","#4daf4a","#984ea3"],5:["#e41a1c","#377eb8","#4daf4a","#984ea3","#ff7f00"],6:["#e41a1c","#377eb8","#4daf4a","#984ea3","#ff7f00","#ffff33"],7:["#e41a1c","#377eb8","#4daf4a","#984ea3","#ff7f00","#ffff33","#a65628"],8:["#e41a1c","#377eb8","#4daf4a","#984ea3","#ff7f00","#ffff33","#a65628","#f781bf"],9:["#e41a1c","#377eb8","#4daf4a","#984ea3","#ff7f00","#ffff33","#a65628","#f781bf","#999999"]},Set2:{3:["#66c2a5","#fc8d62","#8da0cb"],4:["#66c2a5","#fc8d62","#8da0cb","#e78ac3"],5:["#66c2a5","#fc8d62","#8da0cb","#e78ac3","#a6d854"],6:["#66c2a5","#fc8d62","#8da0cb","#e78ac3","#a6d854","#ffd92f"],7:["#66c2a5","#fc8d62","#8da0cb","#e78ac3","#a6d854","#ffd92f","#e5c494"],8:["#66c2a5","#fc8d62","#8da0cb","#e78ac3","#a6d854","#ffd92f","#e5c494","#b3b3b3"]},Set3:{3:["#8dd3c7","#ffffb3","#bebada"],4:["#8dd3c7","#ffffb3","#bebada","#fb8072"],5:["#8dd3c7","#ffffb3","#bebada","#fb8072","#80b1d3"],6:["#8dd3c7","#ffffb3","#bebada","#fb8072","#80b1d3","#fdb462"],7:["#8dd3c7","#ffffb3","#bebada","#fb8072","#80b1d3","#fdb462","#b3de69"],8:["#8dd3c7","#ffffb3","#bebada","#fb8072","#80b1d3","#fdb462","#b3de69","#fccde5"],9:["#8dd3c7","#ffffb3","#bebada","#fb8072","#80b1d3","#fdb462","#b3de69","#fccde5","#d9d9d9"],10:["#8dd3c7","#ffffb3","#bebada","#fb8072","#80b1d3","#fdb462","#b3de69","#fccde5","#d9d9d9","#bc80bd"],11:["#8dd3c7","#ffffb3","#bebada","#fb8072","#80b1d3","#fdb462","#b3de69","#fccde5","#d9d9d9","#bc80bd","#ccebc5"],12:["#8dd3c7","#ffffb3","#bebada","#fb8072","#80b1d3","#fdb462","#b3de69","#fccde5","#d9d9d9","#bc80bd","#ccebc5","#ffed6f"]}};
return colorbrewer;
});
define('utils/isMonotonicallyIncreasing',[],function(){
	//http://stackoverflow.com/questions/2901102/how-to-print-a-number-with-commas-as-thousands-separators-in-javascript
	return function(a){
		return a.every(function(e, i, a) { if (i) return e > a[i-1]; else return true; });
	};
});
define('utils/countDecimals',[],function(){
	//http://stackoverflow.com/questions/2901102/how-to-print-a-number-with-commas-as-thousands-separators-in-javascript
	return function (value) { 
		if ((value % 1) !== 0) 
			return value.toString().split(".")[1].length;  
		return 0;
	};
});
define('utils/hex2rgb',[],function(){
	//http://stackoverflow.com/questions/2901102/how-to-print-a-number-with-commas-as-thousands-separators-in-javascript
	return function (hex) {
		var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
		return result ? {
			r: parseInt(result[1], 16),
			g: parseInt(result[2], 16),
			b: parseInt(result[3], 16),
			a: 1
		} : null;
	};
});
define('giss',['../lib/geostats.min','../lib/colorbrewer.v1.min','./utils/isMonotonicallyIncreasing','./utils/countDecimals','./utils/hex2rgb'],function(geostats,colorbrewer,_isMonotonicallyIncreasing,_countDecimals,_hex2Rgb){
	"use strict";
	var module={};

	/**
	 * Converts class breaks to legend spans.
	 * e.g.: [44222, "72494", "142483", "259726", "323119", 533213] -> [44222, 72494, 72495, 142483, 142484, 259726, 259727, 323119, 323120, 533213]
	 * @param {Array} tcb class breaks as obtained from getClassBreaks or autoClass function
	 * @param g legend resolution (number of decimal places to the next class span)
	 */
	function cb2legend(tcb,g)
	{
		g=Math.pow(10,-g);
		if (tcb.length===0) return tcb;
		var cb=tcb.slice(0);
		var _cb=[];
		var cbinx=0;

		var first=parseFloat(cb.shift());

		for (var i=0;cbinx<cb.length;++i)
		{
			if (i%2===0)
				_cb.push(parseFloat(cb[i-(cbinx++)]));
			else
				_cb.push(parseFloat(_cb[i-1])+g);
		}

		_cb.unshift(first);

		return _cb;
	}

	/**
	 * Returns class breaks only - without calling the checkClassBreaks function
	 * @param {Array} fvval numerical array of variable values to get the class breaks for
	 * @param method 1=quantiles,2=equal intervals,4=jenks natural breaks
	 * @param nc number of classes
	 *
	 */
	function autoClass(fvval,method,nc){
		var gs=new geostats(fvval);
		var cb=[];
		try
		{
			if (method==1)
				cb=gs.getQuantile(nc);
			else if (method==2)
				cb=gs.getEqInterval(nc);
			else if (method==4)
				cb=gs.getJenks(nc);
		}
		catch(err)
		{
			cb=[];
		}
		return cb;
	}

	/**
	 * Checks if class breaks are monotonically increasing and if not it iterates the method and number of classes parameter to get a monotonically increasing class breaks.
	 */

	function checkClassBreaks(fvval,cb,nc,g,method){
		var _cb;
		if (cb.length>0)
		{
			_cb=cb2legend(cb,g);
			if (_isMonotonicallyIncreasing(_cb)) {
				return {cb:cb,nc:nc,m:method};
			}
		}

		var methods=[1,2,4];
		var index = methods.indexOf(method);
		if (index > -1) methods.splice(index, 1);
		methods.unshift(method);

		for (var i=0;i<methods.length;++i)
		{
			method=methods[i];
			for (var tnc=nc-1;tnc>0;tnc--) {
				var ccb=autoClass(fvval,method,tnc);
				_cb=cb2legend(ccb,g);
				if (_cb.length>0)
				{
					if (_isMonotonicallyIncreasing(_cb)) {
						return {cb:ccb,nc:tnc,m:method};
					}
				}
			}
		}
		return false;
	}

	module.cb2legend=function(tcb,g){
		return cb2legend(tcb,g);
	};

	/**
	 * Returns Object {cb,colors} where cb = class breaks using checkClassBreaks function and colors= Array of rgba objects {r,g,b,a};
	 * the length of cb array equals the length of colors array + 1
	 * e.g.: {cb: [1,2,3],colors:[{r:254,g:240,b:217,a:1},{r:3,g:240,b:217,a:1}]}
	 * @param {Array} fvval numerical array of variable values to get the class breaks for
	 * @param {Object} prop properties {cm:classification method, cb: class breaks, cp: color palette}
	 */
	module.getClassBreaks=function(fvval,prop){
		var gs=new geostats(fvval);
		var method=parseInt(prop.cm);
		var nc=parseInt(prop.cb);
		var g=1;	// number of decimal places - used for legend resolution in function cb2legend (see above); TODO: pass number of decimal places as a function parameter
		var cb=[];
		try{
			if (method==1)
				cb=gs.getQuantile(nc);
			else if (method==2)
				cb=gs.getEqInterval(nc);
			else if (method==4)
				cb=gs.getJenks(nc);
		}
		catch(err){
			cb=[];
		}

		var cbr=checkClassBreaks(fvval,cb,nc,g,method);
		if (cbr!==false){
			cbr.colors=module.getColorPalette(prop.cp,cbr.nc);
		}

		return cbr;
	};
	
	/**
	 * Returns color from the input color array that maps to the passed value according to class breaks
	 * @param {Number} a value from the interval [cba[0],cba[cba.length-1]]
	 * @param {Array} cba array of class breaks, e.g. [367, 2425, 2426, 3951, 3952, 6108, 6109, 12213, 12214, 288919]
	 * @param {Array} cbac array of colors, e.g. [{r:254,g:240,b:217,a:255},{r:3,g:240,b:217,a:255},...]
	 */

	module.getColorFromValue=function(value,cba,cbac){
		var ki=0;
		var lngth=cba.length;
		var color=null;
		if (value>=cba[0])
		{
			for (var k=1;k<lngth;k=k+2)
			{
				if (value<=cba[k])
				{
					color=cbac[ki];
					break;
				}
				ki++;
			}
			if (color===null) color=cbac[ki-1];
		}
		
		if (color===null && $.isNumeric(value)) {
			if (value <= cba[0]) {
				color=cbac[0];
			}
		}
		
		return color;
	};
	
	module.getColorPalette=function(cp,nc){
		var nc_real=nc;
		var cp_length=parseInt(_.max(_.keys(colorbrewer[cp])));
		if (nc<3) {
			nc=3;
		}
		else if (nc>cp_length){
			nc=cp_length;
		}
		
		var colors=colorbrewer[cp][nc].slice(0);
		for (var i=0,len=colors.length;i<len;++i){
			colors[i]=_hex2Rgb(colors[i]);
		}
		
		if (nc_real==2){
			colors.splice(1,1);
		}
		else if (nc_real==1){
			colors.splice(1,2);
		}
		
		return colors;
	};
	
	module.preprocessValues=function(variableValues,prop){
		var lvalues=[];
		var g=0;	//the number of decimal places
		variableValues.forEach(function(val){
			if (!isNaN(val)){
				if (prop.decimals===undefined){
					var gtest=_countDecimals(val);
					if (gtest>g) g=gtest;
				}
				lvalues.push(val);
			}
		});
		
		if (prop.decimals===undefined) prop.decimals=g;
		
		return lvalues;
	};

	return module;
});

define('Table',[],function(){
	"use strict";
	var trashIcon='<i class="fa fa-trash" aria-hidden="true"></i>';
	var Table=function(op){
		if (!op) op={};
		this.rid=0;
		this.$tbody=$('<tbody/>');
		this.op=op;
		this.$selectedRow=null;
		this.$table=null;

		var $table=$('<table class="table table-striped table-bordered"></table>');
		this.$table=$table;
		this.updateOptions(op);
		$table.append(this.$tbody);
	};

	Table.prototype.val=function(){
		var a=[];
		this.$tbody.find('tr').each(function(){
			var b=[];
			$('td', $(this)).each(function(){
				b.push($(this).text());
			});
			a.push(b);
		});
		return a;
	};

	Table.prototype.updateOptions=function(op){
		$.extend(this.op, op);
		this.removeAllRows();

		var header=this.op.header;
		if (this.op.removeDefaultClasses) this.$table.removeClass();
		if (this.op.addClass) this.$table.addClass(this.op.addClass);
		if (this.op.trashColumn) header.push(trashIcon);

		if (!this.op.hideHeader){
			var $thead=$('<thead/>');
			var $tr=$('<tr/>');
      if (this.op.checkable===true) $tr.append('<th></th>');

			header.forEach(function(cname){
				$tr.append('<th>'+cname+'</th>');
			});

			$thead.html($tr);
			this.$table.html($thead);

      if (this.op.checkable===true){
        var $th=$thead.find('th:first');
        this._$headCheckBox=$('<input type="checkbox">"');
        var $table=this.$table;
        this._$headCheckBox.click(function(){
          if ($(this).prop('checked')){
            $table.find('.cb-row').prop('checked',true);
          }
          else{
            $table.find('.cb-row').prop('checked',false);
          }
        });
        $th.html(this._$headCheckBox);
      }
    }
	};

	Table.prototype.$el=function(){
		return this.$table;
	};

  Table.prototype.getSelectedRows=function(){
    var checked=[];
    this.$table.find('.cb-row:checked').each(function(){
      checked.push($(this).data('id'));
    });
    return checked;
  };

	Table.prototype.removeAllRows=function(){
		this.$tbody.empty();
		this.rid=0;
		this.$selectedRow=null;
	};

	Table.prototype.addRow=function(rowValues,afterRowId,dataID){
		var id=this.rid++;
		var $tr=$('<tr/>',{id:id});

    if (this.op.checkable===true){
      var $cb=$('<input type="checkbox">"').addClass('cb-row');
      $cb.data('id',dataID);
      var $headCheckBox=this._$headCheckBox;
      var $table=this.$table;
      $cb.click(function() {
        var numAll=$table.find('.cb-row').length;
        if ($table.find('.cb-row:checked').length==numAll){
          $headCheckBox.prop('checked',true);
        }
        else{
          $headCheckBox.prop('checked',false);
        }
      });
      var $td=$('<td/>');
      $td.html($cb);
      $tr.append($td);
    }

		rowValues.forEach(function(td){
      var $td=$('<td/>');
      $td.html(td);
			$tr.append($td);
		});
		if (this.op.trashColumn){
			var $trashIcon=$(trashIcon);
			$trashIcon.data('rid',id);
			$tr.append($('<td/>').html($trashIcon));
			$trashIcon.click($.proxy(function(){
				this.removeRow(id);
			},this));
		}
		if (this.op.selectRowOnClick){
			var that=this;
			$tr.click(function(){
				that.selectRow(id,$tr);
			});
		}

		if (afterRowId!==undefined && afterRowId > -1){
			var $r=this.$tbody.find('tr#'+afterRowId);
			if ($r.length===1){
				$r.after($tr);
				return;
			}
		}

		this.$tbody.append($tr);
		return $tr;
	};

	Table.prototype.selectRow=function(rid,$selectedRow){
		if (this.$selectedRow) this.$selectedRow.removeClass('selected');
		if ($selectedRow)
			this.$selectedRow=$selectedRow;
		else
			this.$selectedRow=this.$tbody.find('#'+rid);

		this.$selectedRow.addClass('selected');

		if (this.op.onRowSelected) this.op.onRowSelected(id);
	};

	Table.prototype.removeRow=function(rid,selected){
		var remove=true;
		
		if (selected!==true && this.op.selectBeforeRemove===true){
			this.selectRow(rid);
			setTimeout(function(){this.removeRow(rid,true);}.bind(this),73);
			return;
		}
		
		if (this.op.confirmBeforeRemove){
			var msg=this.op.confirmBeforeRemoveString!==undefined?
							this.op.confirmBeforeRemoveString:
							'Do you really want to remove selected row?';
			remove=confirm(msg);
		}

		if (remove) {
			var $el=this.$tbody.find('#'+rid);
			var data=$el.data();
			$el.remove();
			if (this.op.onRowRemoved) this.op.onRowRemoved(rid,data);
		}
	};

	return Table;
});

define('utils/numberWithCommas3',[],function(){
	//http://stackoverflow.com/questions/2901102/how-to-print-a-number-with-commas-as-thousands-separators-in-javascript
	return function(x,decimalSign,separatorSign) {
		var parts = x.toString().split(".");
		parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, separatorSign);
		return parts.join(decimalSign);
	};
});
define('ModalDialog',[],function(){
	var tpl='<div class="modal-dialog modal-lg">'+
			'<div class="modal-content">'+
			  '<div class="modal-header">'+
				'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">'+
				  '<span class="pficon pficon-close"></span>'+
				'</button>'+
				'<h4 class="modal-title" id="myModalLabel"></h4>'+
			  '</div>'+
			  '<div>'+
				'<div class="modal-body">'+
				'</div>'+
				'<div class="modal-footer">'+
				'</div>'+
			  '</div>'+
			'</div>'+
		  '</div>';
		  
	var tpl2='<div class="modal-content">'+
                '<div class="popup_header">'+
                    '<span class="close">&times;</span>'+
					'<h4 class="modal-title"></h4>'+
                '</div>'+
                '<div class = "popup_content_container modal-body">'+
                '</div>'+
				'<div class = "popup_content_container modal-footer">'+
                '</div>'+
              '</div>';
		  
	var ModalDialog=function(op){
		if (!op) op={};
		var id='mdlg-'+(new Date()).getTime();
		var $modal=this.$modal=$('<div/>',{
			'class':op.tpl==2?"overlay":"modal fade",
			//'tabindex':"-1", //has to be removed in order for select2 search box to work
			'role':"dialog",
			'aria-hidden':"true",
			id:id
		});
		
		if (op.tpl==2)
			$modal.html(tpl2);
		else
			$modal.html(tpl);
		
		if (op.size){
			$modal.find('.modal-dialog').removeClass('modal-lg').addClass(op.size);	
		}
		
		this.$body=$modal.find('.modal-body').first();
		this.$title=$modal.find('.modal-title').first();
		this.$footer=$modal.find('.modal-footer').first();
		this.$contentDiv=$modal.find('.modal-content').first();
		
		if (op.onShown){
			$modal.on('shown.bs.modal',function(){
				op.onShown();
			});
		}
		
		var that=this;
		
		if (op.acceptBtn){
			var $btn=$('<button type="button" class="btn btn-default">'+op.acceptBtn.text+'</button>');
			
			if (op.acceptBtn.typeClass!==undefined) {
				$btn.removeClass('btn-default').addClass('btn-'+op.acceptBtn.typeClass);
			}
			
			this.$footer.append($btn);
			$btn.click(function(){
				if (op.acceptBtn.callback) op.acceptBtn.callback();
				that.hide();		
			});
		}
    
		if (op.onClose){
			$modal.on('hidden.bs.modal',function(){
				op.onClose();
			});
		}
		
		if (op.tpl==2) {
			$modal.modal=function(action){
				if (action==='show'){
					that.$contentDiv.parent().css({'display': 'inline'});
					that.$modal.trigger('shown.bs.modal');
				}
				else if (action==='hide'){
					that.$contentDiv.parent().css({'display': 'none'});
				}
			};
			
			$modal.find('.close').click(function(){
				that.$contentDiv.parent().css({'display': 'none'});
			});
			
			$('body').prepend($modal);
		}
	};
	
	ModalDialog.prototype.destroy=function(){
		var $backdrop=this.$modal.data()['bs.modal'].$backdrop;
		var $dialog=this.$modal.data()['bs.modal'].$dialog;
		var $element=this.$modal.data()['bs.modal'].$element;
		if ($backdrop) $backdrop.remove();
		if ($dialog) $dialog.remove();
		if ($element) $element.remove();
		this.$modal.data('bs.modal',null);
		this.$modal.remove();
	};
		
	ModalDialog.prototype.show=function(){
		this.$modal.modal('show');
	};
		
	ModalDialog.prototype.hide=function(){
		this.$modal.modal('hide');
	};
	
	return ModalDialog;
});
define('utils/extractFloat',[],function(){
	return function(val,decimalSign,separatorSign){
		
		if (val==='-∞'){
			return Number.NEGATIVE_INFINITY;
		}
		else if (val==='∞'){
			return Number.POSITIVE_INFINITY;
		}
		
		return parseFloat(val.replace(new RegExp("\\"+separatorSign,'g'),'').replace(new RegExp("\\"+decimalSign,'g'),'.'));
	};
});
define('utils/findMinMax',[],function(){
	//http://stackoverflow.com/questions/2901102/how-to-print-a-number-with-commas-as-thousands-separators-in-javascript
	return function findMinMax(values) {
		var mm={min: values[0],max: values[0]};
		for (var i=1,c=values.length;i<c;++i){
		  if (values[i]<mm.min) mm.min=values[i];
		  if (values[i]>mm.max) mm.max=values[i];
		}
		return mm;
	};
});
define('Legend',['./giss','./Table','./utils/numberWithCommas3','./ModalDialog','./utils/extractFloat','./utils/findMinMax'],function(giss,Table,numberWithCommas3,ModalDialog,extractFloat,findMinMax){
	"use strict";
	if (!$) $=jQuery;

	var or_less,or_more;
	var format=function (v,dc,decimalSign,separatorSign){
		if (v===undefined) return;

		if (v===Number.NEGATIVE_INFINITY){
			return '-'+'∞';
		}
		else if (v===Number.POSITIVE_INFINITY){
			return '∞';
		}

		return numberWithCommas3(parseFloat(v).toFixed(dc),decimalSign,separatorSign);
	};

	var _format=function(v,prop){
		return format(v,prop.decimals,prop.decimalSign,prop.separatorSign);
	};

	var prepareItems=function(cba,cbac,prop,categorize){

		if (cba.length<2) return [];
		var ci=0;
		var c1;
		var items=[];
		$.each(cba,function(i,v){
			var item={};
			if (i%2===0){
				c1=v;
			}
			else{
				item.lvalue=c1;
				item.rvalue=v;
				item.cbaInx=i-1;

				if (categorize===true){
					item.text=_format(c1,prop);
				}
				else{
					item.text=_format(c1,prop)+'&nbsp;–&nbsp;'+_format(v,prop);
				}

				item.color=cbac[ci++];
				items.push(item);
			}
		});

		if (categorize && prop.cb.legend_or_less!==undefined) or_less=prop.t['or less'];
		if (categorize && prop.cb.legend_or_more!==undefined) or_more=prop.t['or more'];

		items[0]={text:_format(cba[1],prop)+' '+or_less,color:cbac[0]};
		items[0].lvalue=cba[0];
		items[0].rvalue=cba[1];
		items[0].cbaInx=0;

		var lastInx=items.length-1;
		items[lastInx]={text:_format(cba[cba.length-2],prop)+' '+or_more,color:cbac[cbac.length-1]};
		items[lastInx].lvalue=cba[cba.length-2];
		items[lastInx].rvalue=cba[cba.length-1];
		items[lastInx].cbaInx=cba.length-2;
		return items;
	};

	/**
	 * Creates a legend.
	 * @param {Array} cba legend class regions, e.g. legend with 3 entries "1 or less","2-3","4 or more" is represented as [0,1,2,3,4,5]
	 * @param {Array} cbac legend colors represented as rgba quadruplets, e.g. the legend with two entries (class ranges):[{r:254,g:240,b:217,a:1},{r:3,g:240,b:217,a:1}]. The length of this array should equal cba.length/2
	 */
	var Legend=function(variableValues,prop,op){
		if (!op) op={};

		prop.cm=parseInt(prop.cm);
		if (!prop.t) {
			prop.t={'.':'.',',':',','or less':'or less','or more':'or more','Input a value from the closed interval':'Input a value from the closed interval','Invalid value. Please check if the value falls in the respective interval!':'Invalid value. Please check if the value falls in the respective interval!',
			'Input a value from the interval':'Input a value from the interval'
			};
		}
		
		if (op.t!==undefined) prop.t=op.t;

		function t(key){
			if (prop.t[key]===undefined){
				console.log('Missing translation: '+key);
				return key;
			}

			return prop.t[key];
		}

		or_less=prop.t['or less'];
		or_more=prop.t['or more'];

		if (!prop.decimalSign) prop.decimalSign=prop.t['.'];
		if (!prop.separatorSign) prop.separatorSign=prop.t[','];

		var lvalues=giss.preprocessValues(variableValues,prop);
		var accuracy=(Math.pow(10,-prop.decimals)).toFixed(prop.decimals);
		
		if ((variableValues.length>0 && variableValues.length<3) || (lvalues.length>0 && lvalues.length<3) || prop.cm===8) {
			or_less='';
			or_more='';
			op.edit=false;
		}

		var cbs;
		var cba;
		var cbac;

		if (prop.cm===0 || prop.cba || (variableValues.length>0 && variableValues.length<3) || (lvalues.length>0 && lvalues.length<3) || prop.cm===8){
			var mm;
			if (!prop.cba){
				prop.cba=[];

				if (variableValues.length<3 || lvalues.length<3){
					mm=findMinMax(lvalues);
				}
				else{
					mm={min:Number.NEGATIVE_INFINITY, max:Number.POSITIVE_INFINITY};
				}

				if (prop.cm===8){
					var legend_or_less=prop.cb.or_less;
					var legend_or_more=prop.cb.or_more;
					if (legend_or_less!==undefined && $.isNumeric(legend_or_less)){
						legend_or_less=parseFloat(legend_or_less);
						or_less=prop.t['or less'];
						prop.cb.legend_or_less=legend_or_less;
						prop.cba.push(Number.NEGATIVE_INFINITY, legend_or_less);
					}
					else{
						legend_or_less=Number.NEGATIVE_INFINITY;
					}

					if (legend_or_more!==undefined && $.isNumeric(legend_or_more)){
						or_more=prop.t['or more'];
						legend_or_more=parseFloat(legend_or_more);
						prop.cb.legend_or_more=legend_or_more;
					}
					else{
						legend_or_more=Number.POSITIVE_INFINITY;
					}

					lvalues=_.unique(lvalues);
					lvalues.sort(function(a, b){return a - b;});
					for(var i=0,c=lvalues.length;i<c;++i){
						var val=lvalues[i];
						if (val>legend_or_less && val<legend_or_more){
							prop.cba.push(val);
							prop.cba.push(val);
						}
					}

					if (legend_or_more!==undefined && $.isNumeric(legend_or_more)){
						prop.cba.push(legend_or_more,Number.POSITIVE_INFINITY);
					}
				}
				else{
					prop.cba.push(mm.min);
					if (variableValues.length<3) prop.cba.push(mm.min);
					prop.cba.push(mm.max);
					if (variableValues.length<3) prop.cba.push(mm.max);
				}
			}

			if (!prop.cbac){
				var nc2=parseInt(prop.cba.length/2);
				prop.cbac= prop.inverse_pallete_checkbox ? giss.getColorPalette(prop.cp,nc2).reverse() : giss.getColorPalette(prop.cp,nc2);

				if (nc2>prop.cbac.length) {
					prop.cba.splice(prop.cbac.length*2);

					if (prop.cm===8){
						prop.cb.legend_or_more=prop.cba[prop.cba.length-1];
					}
				}
			}

			cbs={m:prop.cm!==undefined?prop.cm:0,nc:prop.cbac.length};
			cba=prop.cba;
			cbac=prop.cbac;
			if (mm!==undefined && variableValues.length<3 && (Math.abs(mm.min-mm.max)).toFixed(prop.decimals)<accuracy){
				cbac=[cbac[0]];
				cba=[cba[0],cba[0]];
				prop.cbac=cbac;
				prop.cba=cba;
			}
		}
		else{
			if (lvalues.length===0){
				cba=[];cbac=[];cbs={m:prop.cm,nc:parseInt(prop.cb)};
			}
			else{
				cbs=giss.getClassBreaks(lvalues,prop);
				
				if (cbs===false) {
					//try something else
					prop.cm=8;
					Legend.call(this,variableValues,prop,op);
					return;
				}
				
				cba=prop.cba=giss.cb2legend(cbs.cb,prop.decimals);
				if (cba.length>1){
					cba[0]=Number.NEGATIVE_INFINITY;
					cba[cba.length-1]=Number.POSITIVE_INFINITY;
				}
				cbac=prop.cbac=cbs.colors;
			}
		}

		cbac.forEach(function(colorObj){
			colorObj.a=parseInt(parseFloat(colorObj.a)*255);
		});

		if (prop.format){
			format=prop.format;
		}

		//fix class breaks accuracy
		for (var i=0,c=cba.length;i<c;i++){
			cba[i]=parseFloat(cba[i].toFixed(prop.decimals));
		}

		var items=prepareItems(cba,cbac,prop,prop.cm===8,variableValues.length);


		var header=['',''];
		var trashColumn=op.edit;

		if (items.length<2) trashColumn=false;

		var table=this.table=new Table({trashColumn:trashColumn,
									   header:['',''],
									   hideHeader:true,
									   removeDefaultClasses:true,
									   addClass:"table table-bordered",
									   confirmBeforeRemove:op.confirmBeforeRemove,
									   confirmBeforeRemoveString:t['Do you really want to remove selected interval?'],
									   selectBeforeRemove:true,
									   onRowRemoved:onRowRemoved});

		this.table.$el().css('width','100%');

		var $eic;

		if (op.edit){
			header=['','',''];
			var dlgHtml='<div id="edit-interval-content">'+
							'<div class="ilabel"><%=t("Selected interval before edit")%>:</div>'+
							'<div id="ibase" class="ivalue"></div>'+
							'<hr>'+
							'<div class="ilabel"><%=t("Lower interval limit")%>:</div>'+
							'<span class="ilabel helper-text" id="lvalue-help"></span>'+
							'<input id="lvalue">'+
							'<div class="ilabel"><%=t("Upper interval limit")%>:</div>'+
							'<span class="ilabel helper-text" id="rvalue-help"></span>'+
							'<input id="rvalue">'+
							'<div class="ilabel"><%=t("Split the interval at value")%>:</div>'+
							'<span class="ilabel helper-text" id="isplit-help"></span>'+
							'<input id="isplit">'+
						'</div>';
			op.intervalEditContainer.html(_.template(dlgHtml)({t:t}));
			$eic=op.intervalEditContainer.find('#edit-interval-content');
		}

		var onKeyUp=function(e,llimit,rlimit,$inputs,item,itemInx){
			var split=false;
			if (e.which==13) {
				split=true;
			}
			var $this=$(this);
			$this.removeClass('legend-error');
			var strval=$this.val();

			if (strval.substr(strval.length-1)!==prop.decimalSign){
				var val=extractFloat(strval,prop.decimalSign,prop.separatorSign);
				if (val){
					var dc=prop.decimals;
					if (strval.indexOf(prop.decimalSign)==-1) dc=0;
					$this.val(format(val,dc,prop.decimalSign,prop.separatorSign));
				}
			}

			onBlur(llimit,rlimit,$inputs,item,itemInx,split,true);
			return true;
		};

		function editInterval(item,itemInx,editStart,unformatted){
			if (editStart===true) {
				$eic.show();
				table.selectRow(itemInx);
				$eic.find('.legend-error').removeClass('legend-error');
			}

			var intervalParenthesesLeft='[';
			var intervalParenthesesRight=']';
			var $lvalueInput=$eic.find('#lvalue').prop('disabled',false);
			var $rvalueInput=$eic.find('#rvalue').prop('disabled',false);
			var $isplitInput=$eic.find('#isplit').prop('disabled',false);

			$eic.find('.helper-text').html('');

			var $inputs={$lvalueInput:$lvalueInput,$rvalueInput:$rvalueInput,$isplitInput:$isplitInput};

			var llimit,rlimit;

			if (item.lvalue===Number.NEGATIVE_INFINITY) {
				$lvalueInput.prop('disabled',true);
				intervalParenthesesLeft='(';
			}
			else{
				var previousInterval=items[itemInx-1];
				if (previousInterval!==undefined){
					if (previousInterval.lvalue===Number.NEGATIVE_INFINITY) {
						llimit=Number.NEGATIVE_INFINITY;
					}
					else{
						llimit=previousInterval.lvalue+2*accuracy;
						if ((llimit-accuracy)-previousInterval.lvalue < accuracy){ //if previous interval width is less than accuracy
							llimit=item.lvalue;
						}
					}
				}
				
				$eic.find('.helper-text#lvalue-help').html(_.template(t('Input a value betwen <%=m1%> and <%=m2%>'))({m1:_format(llimit,prop),m2:_format(item.rvalue-accuracy,prop)}));
			}

			if (item.rvalue===Number.POSITIVE_INFINITY) {
				$rvalueInput.prop('disabled',true);
				intervalParenthesesRight=')';
			}
			else{
				var nextInterval=items[itemInx+1];
				if (nextInterval!==undefined){
					if (nextInterval.rvalue===Number.POSITIVE_INFINITY) {
						rlimit=Number.POSITIVE_INFINITY;
					}
					else{
						rlimit=nextInterval.rvalue-2*accuracy;
						if (nextInterval.rvalue-(rlimit+accuracy) < accuracy){ //if next interval width is less than accuracy
							rlimit=item.rvalue;
						}
					}
				}
				
				$eic.find('.helper-text#rvalue-help').html(_.template(t('Input a value betwen <%=m1%> and <%=m2%>'))({m1:_format(item.lvalue+accuracy,prop),m2:_format(rlimit,prop)}));
			}

			$isplitInput.val('');
			if (editStart===true){
				$eic.find('#ibase').html(intervalParenthesesLeft+
								 _format(item.lvalue,prop)+', '+
								 _format(item.rvalue,prop)+
								 intervalParenthesesRight
								 );
			}

			if (editStart===true || unformatted!==true){	//prevent double formatting after keyup event
				$lvalueInput.val(_format(item.lvalue,prop));
				$rvalueInput.val(_format(item.rvalue,prop));
			}

			if (item.rvalue-item.lvalue<=2*accuracy) {
				$isplitInput.prop('disabled',true);
			}
			else{
				$eic.find('.helper-text#isplit-help').html(_.template(t('Input a value betwen <%=m1%> and <%=m2%> and press the ENTER key.'))({m1:_format(item.lvalue+accuracy,prop),m2:_format(item.rvalue-2*accuracy,prop)}));
			}

			$lvalueInput.off('blur').on('blur',function(){onBlur(llimit,rlimit,$inputs,item,itemInx);});
			$rvalueInput.off('blur').on('blur',function(){onBlur(llimit,rlimit,$inputs,item,itemInx);});
			$isplitInput.off('blur').on('blur',function(){onBlur(llimit,rlimit,$inputs,item,itemInx);});

			$lvalueInput.off('keyup').on('keyup',function(e){onKeyUp.bind($(this))(e,llimit,rlimit,$inputs,item,itemInx);});
			$rvalueInput.off('keyup').on('keyup',function(e){onKeyUp.bind($(this))(e,llimit,rlimit,$inputs,item,itemInx);});
			$isplitInput.off('keyup').on('keyup',function(e){onKeyUp.bind($(this))(e,llimit,rlimit,$inputs,item,itemInx);});
		}

		function onBlur(llimit,rlimit,$inputs,item,itemInx,split,unformatted){
			var result={lvalue:extractFloat($inputs.$lvalueInput.val(),prop.decimalSign,prop.separatorSign),
				rvalue:extractFloat($inputs.$rvalueInput.val(),prop.decimalSign,prop.separatorSign),
				isplit:extractFloat($inputs.$isplitInput.val(),prop.decimalSign,prop.separatorSign),
			};

			$.each($inputs,function(key,$obj){$obj.removeClass('legend-error');});

			var hasErrors=false;

			/*
			 * puts defaultValue if currentValue isNaN
			 */
			function defaultInputValue(currentValueKey,defaultValue,$input){
				if (isNaN(result[currentValueKey])) {
					result[currentValueKey]=defaultValue;
					$input.val(_format(defaultValue,prop));
					$input.select();
				}
			}

			//check lvalue
			if ($inputs.$lvalueInput.prop('disabled')===false){
				defaultInputValue('lvalue',item.lvalue,$inputs.$lvalueInput);
				if (!(result.lvalue>=llimit && result.lvalue<=item.rvalue-accuracy)){
					$inputs.$lvalueInput.addClass('legend-error');
					hasErrors=true;
				}
			}

			//check rvalue
			if ($inputs.$rvalueInput.prop('disabled')===false){
				defaultInputValue('rvalue',item.rvalue,$inputs.$rvalueInput);
				if (!(result.rvalue>=item.lvalue+accuracy && result.rvalue<=rlimit)){
					$inputs.$rvalueInput.addClass('legend-error');
					hasErrors=true;
				}
			}

			//check split value
			if ($inputs.$isplitInput.prop('disabled')===false){
				if ($inputs.$isplitInput.val()!=''){
					if (!(item.lvalue+accuracy<=result.isplit && result.isplit<=item.rvalue-2*accuracy)){
						$inputs.$isplitInput.addClass('legend-error');
						hasErrors=true;
					}
				}
			}

			if (hasErrors) return false;

			if (itemInx===0 && $inputs.$rvalueInput.prop('disabled')===false) {
				cba[itemInx*2+1]=result.rvalue;
				cba[itemInx*2+2]=result.rvalue+accuracy;
			}
			else if (itemInx==(items.length-1) && $inputs.$lvalueInput.prop('disabled')===false){
				cba[itemInx*2-1]=result.lvalue-accuracy;
				cba[itemInx*2]=result.lvalue;
			}
			else if ($inputs.$lvalueInput.prop('disabled')===false && $inputs.$rvalueInput.prop('disabled')===false){
				cba[itemInx*2-1]=result.lvalue-accuracy;
				cba[itemInx*2]=result.lvalue;
				cba[itemInx*2+1]=result.rvalue;
				cba[itemInx*2+2]=result.rvalue+accuracy;
			}

			var editStart=false;
			if ($inputs.$isplitInput.val()!='' && split===true){ //split
				cba.splice(itemInx*2+1, 0, result.isplit);
				cba.splice(itemInx*2+2, 0, result.isplit+accuracy);
				cbac=giss.getColorPalette(prop.cp,cbac.length+1);
				editStart=true;
			}

			var changedItems=prepareItems(cba,cbac,prop);

			if (_.isEqual(items,changedItems)) return false;

			items=changedItems;

			table.removeAllRows();

			if (items.length>1) table.updateOptions({trashColumn:true});

			refreshTable(items);
			table.selectRow(itemInx);

			editInterval(items[itemInx],itemInx,editStart,unformatted);

			return true;
		}

		function _getData(){
			var cb=prop.cm==8?prop.cb:cbs.nc;
			return {decimals:prop.decimals,cba:cba,cbac:cbac,cm:cbs.m,cb:cb,cp:prop.cp,t:prop.t};
		}

		function refreshTable(items){
			var iconPlus=true;
			if (items.length==9) iconPlus=false;
			items.forEach(function(item,itemInx){
				addItemToTable(item,itemInx,iconPlus);
			});

			if (op.onTableRefreshed){
				op.onTableRefreshed(_getData());
			}
		}

		function onRowRemoved(rid,data){
			var cbaInx=rid*2;
			if (cbaInx>0) {
				cba[cbaInx-1]=cba[cbaInx+1];
				cba.splice(cbaInx+1, 1);
				cba.splice(cbaInx, 1);
			}
			else {
				cba.splice(cbaInx+1, 1);
				cba.splice(cbaInx+1, 1);
			}

			cba[0]=Number.NEGATIVE_INFINITY;
			cba[cba.length-1]=Number.POSITIVE_INFINITY;

			cbac=giss.getColorPalette(prop.cp,cbac.length-1);

			table.removeAllRows();
			items=prepareItems(cba,cbac,prop);

			if (items.length==1){
				table.updateOptions({trashColumn:false});
			}

			refreshTable(items);
			$eic.hide();
		}

		var addItemToTable=function(item,itemInx,iconPlus){
			if (iconPlus===undefined) iconPlus=true;
			var row;
			var ctd;
			if (op.edit===true){
				ctd=1;
				var $pencil=$('<i class="fa fa-pencil" aria-hidden="true"></i>');
				row=[$pencil,'',item.text];
				$pencil.click(function(){
					editInterval(item,itemInx,true);
					if (op.onIntervalEdit) op.onIntervalEdit();
				});
			}
			else{
				ctd=0;
				row=['',item.text];
			}
			var $tr=table.addRow(row);
			var $tdc=$tr.find('td').eq(ctd);
			$tdc.css('background-color','rgb('+item.color.r+','+item.color.g+','+item.color.b+')');
			$tdc.css('width','3em');
		};

		refreshTable(items);

		this.$eic=$eic;	//interval editor div

		this.getData=function(){
			return _getData();
		};
	};

	Legend.prototype.$el=function(){
		return this.table.$el();
	};

	Legend.prototype.hideManualIntervalEditor=function(){
		if (this.$eic!==undefined){
			this.$eic.hide();
		}
	};

	Legend._addSpecialValue=function(sv,table,addEye){
		sv.color.a=parseInt(parseFloat(sv.color.a)*255);
		var row;
		var eq;
		if (addEye===true){
			row=['<i class="icon ion-eye"></i>','',sv.legend_caption];
			eq=1;
		}
		else{
			row=['',sv.legend_caption];
			eq=0;
		}

		var $tr=table.addRow(row);
		var $tdc=$tr.find('td').eq(eq);
			$tdc.css('background-color','rgb('+sv.color.r+','+sv.color.g+','+sv.color.b+')');
			$tdc.css('width','3em');
	};

	Legend.prototype.addSpecialValue=function(sv){
		Legend._addSpecialValue(sv,this.table);
	};

	return Legend;
});

