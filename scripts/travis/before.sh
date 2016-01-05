#!/usr/bin/env sh

# prepare some env variables
ZIP_EXCLUDE_FILE=${TRAVIS_BUILD_DIR}/vendor/coldtrick/releases/scripts/travis/exclude.lst

# remove composer dev dependencies
echo "Removing Composer development dependencies"
composer install --no-dev

# start creating the zip file
echo "Creating zip"
# go down one directory
cd ..
# create zip with some excluded paths
- zip -r $PROJECTNAME/${PROJECTNAME}_${TRAVIS_TAG}.zip $PROJECTNAME -x@${ZIP_EXCLUDE_FILE}
# return to the main directory for the next step
- cd $PROJECTNAME

# done
echo "Done with preprocessing"