#!/bin/bash

# remove previously installed adminer
sudo rm adminer.php 2>/dev/null
sudo rm adminer.css 2>/dev/null

# download adminer with style
sudo wget https://github.com/vrana/adminer/releases/download/v4.6.2/adminer-4.6.2.php
sudo wget https://raw.githubusercontent.com/vrana/adminer/master/designs/hever/adminer.css

# rename to adminer.php
mv adminer-4.6.2.php adminer.php

echo "Adminer installed"
