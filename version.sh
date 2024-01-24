#!/bin/bash

# Define the file paths
PACKAGE_JSON="package.json"
PACKAGE_LOCK_JSON="package-lock.json"
INFO_XML="appinfo/info.xml"

# Function to increment version numbers
increment_version() {
  local version=$1
  local part=$2
  local IFS='.'
  read -ra parts <<< "$version"
  case $part in
    major)
      ((parts[0]++))
      parts[1]=0
      parts[2]=0
      ;;
    minor)
      ((parts[1]++))
      parts[2]=0
      ;;
    patch)
      ((parts[2]++))
      ;;
  esac
  echo "${parts[0]}.${parts[1]}.${parts[2]}"
}

# Check if the correct number of arguments is given
if [ "$#" -ne 1 ]; then
  echo "Usage: $0 {major|minor|patch}"
  exit 1
fi

# Check if the argument is valid
if [[ ! $1 =~ ^(major|minor|patch)$ ]]; then
  echo "Error: Argument must be 'major', 'minor', or 'patch'"
  exit 1
fi

# Extract the current version from package.json using awk
current_version=$(awk -F'"' '/"version":/ {print $4}' "$PACKAGE_JSON")
if [ -z "$current_version" ]; then
  echo "Error: Unable to extract current version from $PACKAGE_JSON"
  exit 1
fi

# Increment the version
new_version=$(increment_version "$current_version" "$1")

# Update package.json
sed -i '' "s/\"version\": \"$current_version\"/\"version\": \"$new_version\"/" "$PACKAGE_JSON"

# Update package-lock.json
sed -i '' "s/\"version\": \"$current_version\"/\"version\": \"$new_version\"/" "$PACKAGE_LOCK_JSON"

# Update the empty version property in package-lock.json
sed -i '' "/\"\"/,/}/ s/\"version\": \".*\"/\"version\": \"$new_version\"/" "$PACKAGE_LOCK_JSON"

# Update appinfo/info.xml
sed -i '' "s|<version>$current_version</version>|<version>$new_version</version>|" "$INFO_XML"

echo "Version updated to $new_version"