#!/bin/bash

cd .
node node_modules/.bin/grunt build

read -n 1 -s -r -p "Press any key to continue . . ."