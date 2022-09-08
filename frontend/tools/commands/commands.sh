#!/bin/bash

tools_path="/var/www/html/tools"
components_path="/var/www/html/tools/components"

# load config and utils
source "$tools_path/config.sh"
source "$tools_path/_utils/utils.sh"

# Main function to call OS and Project functions
function tools {
    local command=$1
    local environment=$2

    if [ "$command" == "list_commands" ]; then
        # Buils list of all available commands - used for listing and autocomplete
        for file in $(find $components_path -name '*.sh');
        do
            if [[ -f $file ]]; then
                f="${file%.*}"
                echo "${f#$components_path/}"
            fi
        done
    elif [ "$command" == "list_environments" ]; then
        # Buils list of all available environments - used for listing and autocomplete
        for i in "${!COMMANDS_ENVIRONMENT_GROUPS[@]}"
        do
            echo $i
        done
    elif [ "$command" == 'refresh' ]; then
        # refreshes command list and available enviromnents
        echo "Attempting to reload bash ..."
        exec bash
    else
        # executes commands
        local script_file=$components_path"/"$command".sh"

        # check if script exists
        if [ -f "$script_file" ]; then

            # check if environment group exists
            if [ ${COMMANDS_ENVIRONMENT_GROUPS[$environment]+is_set} ]; then
                echo -e "${yellow}Running $command for environment: "${COMMANDS_ENVIRONMENT_GROUPS[$environment]}${NC}

                # iterate all environments in enviroment group, and execute command for each environment
                for i in $(echo ${COMMANDS_ENVIRONMENT_GROUPS[$environment]} | sed "s/,/ /g")
                do

                    if [ "$command" == "os/connect" ]; then
						local connect_command="";
						if [[ "${environment,,}" = *"test"* ]]; then
							connect_command="PS1='\${debian_chroot:+(\$debian_chroot)}\[\033[01;33m\]\u@\h\[\033[00m\]:\[\033[01;33m\]\w\[\033[00m\]\$ '"
						elif [[ "${environment,,}" = *"prod"* ]]; then
							connect_command="PS1='\${debian_chroot:+(\$debian_chroot)}\[\033[01;31m\]\u@\h\[\033[00m\]:\[\033[01;31m\]\w\[\033[00m\]\$ '"
						elif [[ "${environment,,}" = *"dev"* ]]; then
							connect_command="PS1='\${debian_chroot:+(\$debian_chroot)}\[\033[01;31m\]\u@\h\[\033[00m\]:\[\033[01;31m\]\w\[\033[00m\]\$ '"
						fi
                        run_remote_command $i $command".sh" "bash && $connect_command";
                    else
                        run_remote_command $i $command".sh";
                    fi

                done

            else
                echo -e "${RED}Command environment group NOT found! - check COMMANDS_ENVIRONMENT_GROUPS in your config.sh${NC}"
            fi
        else
        	echo -e "${RED} $script_file not found!${NC}"
        fi
    fi
}
