define([],function(){
	"use strict";
	if (!$) $=jQuery;

	const SuNote = function(data) {
        const $el = $('#su-notes-cmp');
        $el.empty();

        data = data || [];

        this.nid = 0;

        this.data = {};

        data.map(row=>{
            this.data[row.id] = [row.sid,row.note];
        });

        const $table = $('<table/>');

        $table.append('<tr><th style="width:30%">Spatial unit ID</th><th style="width:60%">Note</th><th></th></tr>');

        $el.append($table);

        const $tbody = this.$tbody = $('<tbody/>');
        $table.append($tbody);

        const $inputDiv = $('<div/>',{
            style:'display:flex'
        });

        const $inputID = $('<input style="width:50%" placeholder="input spatial unit ID">');

        const $inputNote = $('<input style="width:100%" placeholder="input note for spatial unit ID">');

        const $btnAdd = $('<input type="button" value="Add">');

        $inputDiv.append($inputID);
        $inputDiv.append($inputNote);

        $inputDiv.append($btnAdd);

        $el.append($inputDiv);

        this.$drupalElement = $('[name="manual_parameters[manual_param_input][su_notes][notes]"]')
        
        $btnAdd.on('click', () => { 
            const sid = $inputID.val().trim();
            const note = $inputNote.val().trim();

            if (sid==='' || note==='') {
                alert('Izpolnjeni morata biti obe polji.');
                return;
            }

            const nid = '_'+(this.nid);
            this.nid++;

            this.data[nid] = [sid, note];

            this.render();

            $inputID.val('');
            $inputNote.val('');
        });

        this.render();
    }

    SuNote.prototype.render = function() {
        this.$tbody.empty();

        const out = [];

        for (const [id, value] of Object.entries(this.data)) {
            const $tr = $('<tr/>',{'id':id});
            const [sid, note] = value;

            out.push({id,sid,note});
            $tr.append(`<td>${sid}</td>`);
            $tr.append(`<td>${note}</td>`);
            const $delete = $('<i class="fa fa-trash" aria-hidden="true"></i>');
            const $td = $('<td/>');
            $td.html($delete);
            $delete.on('click', () => {
                const id = $tr.attr('id');
                delete this.data[id];
                this.render();
            });

            $tr.append($td);
            this.$tbody.append($tr);
        };

        this.$drupalElement.val(JSON.stringify(out));
    }

	return SuNote;
});
