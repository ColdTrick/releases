# Travis support

The scripts in this folder will aid in the build process on Travis.

## How to setup

Check the INSTALL.md file

## Tests

Currently there are no tests setup for a build, this will come in the future.

## Deployment

The settings for deployment will create a zip-file which will be added to a GitHub release when you create a tag on GitHub.
This zip-file will be named `<your-project>_<tag-name>.zip` (eg. profile_manager_v10.1.3.zip)

Note: if you update an existing tag which already has the created zip-file in the release it won't be overridden with the new content.
