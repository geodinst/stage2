#!/bin/bash

if [ "$ENV" == "DEVELOPMENT" ];
then
    echo "Setup TOOLS"

    alter_bashrc="source /var/www/html/tools/commands/commands.sh"
    if grep "$alter_bashrc" /home/ubuntu/.bashrc > /dev/null
    then
        echo "TOOLS already installed"
    else
        echo "$alter_bashrc" >> /home/ubuntu/.bashrc

        # create project settings
        sudo touch /etc/bash_completion.d/tools
        sudo chmod 666 /etc/bash_completion.d/tools
        sudo cat <<EOF > /etc/bash_completion.d/tools
_tools() {
    _script_commands=\$(tools list_commands)
    _script_environments=\$(tools list_environments)

    local cur prev
    COMPREPLY=()
    cur="\${COMP_WORDS[COMP_CWORD]}"

    if [ \$COMP_CWORD -eq 1 ]; then
        COMPREPLY=( \$(compgen -W "\${_script_commands}" -- \${cur}) )
    elif [ \$COMP_CWORD -eq 2 ]; then
        COMPREPLY=( \$(compgen -W "\${_script_environments}" -- \${cur}) )
    fi

    return 0
}
complete -F _tools tools
EOF
        sudo chmod 644 /etc/bash_completion.d/tools

        echo "TOOLS installed"
    fi
fi
