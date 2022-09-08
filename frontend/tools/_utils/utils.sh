#!/bin/bash

# WILL GET POPULATED WITH CURRENT ENV VARS
declare -A CONFIG

# declare COLORS
red='\033[0;31m'
green='\033[0;32m'
yellow='\033[1;33m'
RED='\033[1;91m'
GREEN='\033[1;92m'
YELLOW='\033[1;93m'
NC='\033[0m'

function execute_component {
    local SCRIPT="$1"

    # copy config associative array
    if [ -z ${ENV+x} ];
    then
        copy_associative_array "DEVELOPMENT" "CONFIG"
    else
        copy_associative_array "$ENV" "CONFIG"
    fi

    echo " "
    echo -e "${yellow}Executing component $SCRIPT...${NC}"

    # save current dir
    current_dir=`pwd`

    # run script
    source "$SCRIPT"

    # check script return
    if [ $? -eq 0 ]; then
        echo -e "${green}$SCRIPT executed successfully${NC}"
    else
        echo -e "${RED}Component execution failed${NC}"
        echo -e "${red}Execution aborted${NC}"
        exit 1
    fi

    # restore current dir
    cd $current_dir
}

# builds remote command to send it through ssh
# $1 local script
# $2 config
# $3 added commands (I.E. 'bash', to stay connected to remote server, and keep shell session open)
function run_remote_command {
    local ENV="$1"
    local LOCAL_SCRIPT="$2"
    local ADDED_COMMANDS="$3"

    copy_associative_array "$ENV" "CONFIG"

    config_declaration=$(declare -p CONFIG)
    input=/var/www/html/tools/components/"$LOCAL_SCRIPT"
    prepared=""

    # get file contents
    while IFS= read -r line
    do
        if [ "$line" == '#!/bin/bash' ]; then
            line="${line}
${config_declaration}"
        fi
        prepared="${prepared}${line}
"
    done < "$input"

    # convert file content to base64
    FILE_CONTENT=$(echo -n "$prepared" | base64 -w0 )

    # set remote script name
    DEST_F_NAME="tools/00_remote_script.sh"

    echo ${CONFIG[host]}
    echo ${CONFIG[project_root]}

    # create directory if missing and cd into it
    # create new file on remote
    # put contents of local file into remote file
    # set executable attr on remote file
    # set env variable and run remote file
    # remove remote file
    remote_command='
        echo '${CONFIG[pass]}' | sudo -S mkdir -p '${CONFIG[project_root]}';
        cd '${CONFIG[project_root]}';
        sudo mkdir -p tools;
        sudo touch '$DEST_F_NAME';
        sudo echo '$FILE_CONTENT' | base64 -d | sudo tee '$DEST_F_NAME' > /dev/null;
        sudo chmod +x '${CONFIG[project_root]}'/'$DEST_F_NAME';
        echo -e "\n";
        sudo ENV='$ENV' '${CONFIG[project_root]}'/'$DEST_F_NAME';
        sudo rm '${CONFIG[project_root]}'/'$DEST_F_NAME';
        '$ADDED_COMMANDS'
    '

    sshpass -p ${CONFIG[pass]} ssh -o "StrictHostKeyChecking no" -t -l ${CONFIG[user]} ${CONFIG[host]} "$remote_command"
}

# makes a file monolithic - inserts all source files into one large file
# $1 main file path
# $2 output file path
function monolithic {
    readonly MAIN=$1
    readonly MONOLITHIC=$2

    [ -f "$MONOLITHIC" ] && cp "$MONOLITHIC" "${MONOLITHIC}.bak"

    ## to extract sourced filename
    regex='^\([[:space:]]*\)source[[:space:]]\+\([^[:space:]]\+\)[[:space:]]*$'

    IFS=$'\n'  ## retain whitespace

    while read main_line; do
        sourced=$(echo "$main_line" | sed -n "s/$regex/\2/p")
        if [ -n "$sourced" ]; then
            indent=$(echo "$main_line" | sed -n "s/$regex/\1/p")
            while read sourced_line; do
                echo "${indent}${sourced_line}" >> "$MONOLITHIC"
            done < "$sourced"
        else
            echo "$main_line" >> "$MONOLITHIC"
        fi
    done < "$MAIN"

    unset IFS

    [ -f "${MONOLITHIC}.bak" ] && rm "${MONOLITHIC}.bak"
}

# copy associative array
# $1 original array
# $2 copy to array
function copy_associative_array {
    local original="$1"
    local copy="$2"

    local __copy__=$(declare -p $original);
    eval declare -A __copy__="${__copy__:$(expr index "${__copy__}" =)}";

    for i in "${!__copy__[@]}"
    do
        # double qoutes prevenrs variable expansion!
        eval ${copy}[$i]="\"${__copy__[$i]}\""
    done
}
