# Installation instructions

In order to get Travis deployment working follow the steps below

1. edit/add the composer.json and add to the `require-dev` => "coldtrick/releases": "dev-master"
2. run `composer update`
3. copy the file `scripts/travis/.travis.yml.sample` to `.travis.yml`
4. run `travis setup releases --force` to set the correct GitHub API key (requires local Travis installed)
5. copy the contents of the file `.travis.yml` to a temporary text file (eg notepad)
6. replace the `.travis.yml` file with the sample file (`scripts/travis/.travis.yml.sample`)
7. copy the API key from the temporary text file to `.travis.yml`
8. commit the changes
9. go to https://travis-ci.org/ and enable Travis for your project

Optional

10. check the settings on Travis to only run if a `.travis.yml` file is present
 