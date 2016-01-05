#!/usr/bin/env sh

# remove composer dev dependencies
echo "Removing Composer development dependencies"
composer install --no-dev

# start creating the zip file
echo "Creating zip"
# go down one directory
cd ..
# create zip with some excluded paths
zip -r $PROJECTNAME/${PROJECTNAME}_${TRAVIS_TAG}.zip $PROJECTNAME -x $PROJECTNAME/.git/**\* $PROJECTNAME/.git/ $PROJECTNAME/**/.git/**\* $PROJECTNAME/**/.git/ $PROJECTNAME/vendor/coldtrick/releases/**\* $PROJECTNAME/vendor/coldtrick/releases/ > /dev/null
# return to the main directory for the next step
cd $PROJECTNAME

# done
echo "Done with preprocessing"