#!/usr/bin/env bash

# Check .env file
if [ ! -f ./.env ]; then
    echo "File .env not found!"
    exit
fi

# Initialize
heroku_set="heroku config:set "

# Parse .env file and loop on file lines
while read l; do
  # Check for empty or comments lines
  if [[ -z ${l} ]] || [[ ${l} == \#* ]] ;
  then
    continue
  fi

  # Check for dev environment and convert to prod
  if [[ ${l} == "APP_ENV=dev" ]] ;
  then
    l="APP_ENV=prod"
  fi

  # Remove quotes
  l=$(echo "$l" | tr -d "'")

  # Add config to line
  heroku_set="$heroku_set $l"
done <.env

# Execute Heroku set config
eval "${heroku_set}"
