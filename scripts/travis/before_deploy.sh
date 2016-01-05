#!/usr/bin/env sh

echo "Preparing release"
# Go down one directory
cd ..
# Create a new temp directory
mkdir zip_temp
# Enter the directory
cd zip_temp
# Copy the plugin to the new temp location
echo "Copying plugin to temp location"
cp -a ../$PROJECTNAME ./
# Enter the plugin directory
cd $PROJECTNAME
# Remove Composer development dependencies
echo "Removing Composer development dependencies"
composer install --no-dev
# Go to the temp dir
cd ..
# Create the zip file
echo "Creating zip-file"
zip -r ../$PROJECTNAME/${PROJECTNAME}_${TRAVIS_TAG}.zip $PROJECTNAME -x $PROJECTNAME/.git/**\* $PROJECTNAME/.git/ $PROJECTNAME/**/.git/**\* $PROJECTNAME/**/.git/ > /dev/null
# Some cleanup actions
echo "Cleaning up"
cd ..
rm -rf zip_temp
# Go back to original location
cd $PROJECTNAME
echo "Done with preparations"