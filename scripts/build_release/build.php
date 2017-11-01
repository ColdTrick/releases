<?php

// REMINDER; git ssh config keys: setx HOME c:\Users\admin\ on the command line.

// line endings: git config --global core.autocrlf true

// setting global ignore: git config --global core.excludesfile ~/.gitignore_global

// git config --global user.email "you@example.com"
// git config --global user.name "Your Name"

// non master releases
// git branch --set-upstream-to=origin/<branch> <local branch>

$current_path = getcwd();
$folders = explode('\\', $current_path);
$reponame = get_repo_name();

// check repo
if (empty($reponame)) {
	lpr('No reponame found. Going for a walk... Bye bye!', true);
}
if (strtolower(ask("Assuming plugin '$reponame' is being released. Is this correct? Y/N")) !== 'y') {
	lpr('Goodbye', true);
}

// get Github api token
$user_token = get_github_token();
if (empty($user_token)) {
	lpr('', true);
}
lpr('');

// git pull latest version
$output = shell_exec('git pull');
if ($output === null) {
	lpr('Failure detected. Exitting', true);
}

// check if up to date
if (trim($output) !== 'Already up-to-date.') {
	lpr('Local branch is not up to date. Exitting.', true);
}

// check for local changes
$status = shell_exec('git status --porcelain');
if (!empty($status)) {
	lpr('Detected changes. Commit those before building release. Exitting.', true);
}

// detect latest tag
$previous_tag = shell_exec('git describe --tags --abbrev^=0');
lpr(trim($previous_tag) . ' is the latest tag used to generate the release notes.');

// generate release notes
$release = generate_release_notes($previous_tag);
if (empty($release)) {
	lpr('');
	lpr('No release notes, so assuming no release is needed. Exitting.', true);
}

// present commit counter
$release_counter = $release['counter'];
$commit_counter = 'Commmit counter:';
foreach ($release_counter as $type => $count) {
	if (empty($count)) {
		continue;
	}
	$commit_counter .= " $type: {$count}";
}
lpr($commit_counter);
lpr('');

$release_notes = $release['notes'];
if (empty($release_notes)) {
	if (strtolower(ask('Looks like only chores are in this release, still release? Y/N')) !== 'y') {
		lpr('OK stopping, bye!', true);
	}
	
	lpr('');
	$release_notes[] = '- several small chores/fixes' . PHP_EOL;
}

lpr('Release notes:');
lpr(implode('', $release_notes));

$new_version = ltrim(ask('What is the new tag?'), 'vV');
if (empty($new_version)) {
	lpr('Missing new version number', true);
}
lpr('');

// check for existence of CHANGES.txt
$changes_file = $current_path . '\CHANGES.txt';

if (!file_exists($changes_file)) {
	file_put_contents($changes_file, 'Version history' . PHP_EOL . '===============' . PHP_EOL);
	// add the new file to git
	shell_exec("git add CHANGES.txt");
}

$changes_content = file($changes_file);

if (!isset($changes_content[0]) || trim($changes_content[0]) !== 'Version history') {
	lpr('Did not find the text "Version history" on the first line in the file: ' . $changes_file, true);
}

if (!isset($changes_content[1]) || trim($changes_content[1]) !== '===============') {
	lpr('Did not find the text "===============" on the second line in the file: ' . $changes_file, true);
}

// check manifest
$manifest_file = $current_path . '\manifest.xml';
$manifest_found = true;
if (!file_exists($manifest_file)) {
	$manifest_found = false;
	if (strtolower(ask("No manifest file found at: {$manifest_file}. Continue anyway? Y/N")) !== 'y') {
		lpr("Exitting", true);
	}
}

if ($manifest_found) {
	$manifest_contents = file($manifest_file);
	$version_updated = false;
	foreach ($manifest_contents as $key => $line) {
		$version_pattern = '/<version>\S+<\/version>/';
		if (preg_match($version_pattern, $line)) {
			$manifest_contents[$key] = preg_replace($version_pattern, "<version>{$new_version}</version>", $line);
			$version_updated = true;
			break;
		}
		
		if (preg_match('/<license>\S+<\/license>/', $line)) {
			// we now found the license tag, but still no version
			// giving up!
			lpr("Could not find <version> element in the manifest file at an expected location. Exitting.", true);
		}
	}
	
	if ($version_updated === false) {
		lpr("Read the whole manifest file, but found no version element to update. Exitting.", true);
	}
	
	// update manifest
	file_put_contents($manifest_file, implode('', $manifest_contents));
	shell_exec("git add {$manifest_file}");
}

// update CHANGES.txt
$date = date('Y-m-d');

$header = [];

$header[] = array_shift($changes_content); //remove line 1
$header[] = array_shift($changes_content); //remove line 2
$header[] = PHP_EOL;
$header[] = "{$new_version} ({$date}):" . PHP_EOL . PHP_EOL;

$new_array = array_merge($header, $release_notes, $changes_content);

// write new content
file_put_contents($changes_file, implode('', $new_array));

// ask for last minute changes
lpr('Release notes and the manifest have been updated. You can manually check the output if needed.');
ask('Press ENTER to continue.');

// add file to git commit
shell_exec("git add CHANGES.txt");

// do all validation and ask for a confirm
lpr('Starting Release');

// commit new version
$commit_message = "chore: wrapping up v{$new_version}";
shell_exec("git commit -m \"{$commit_message}\"");

// create new tag for version
shell_exec("git tag -m \"Version {$new_version}\" v{$new_version}");

// push to github.com
shell_exec("git push origin HEAD --tags");

// update release text on github.com release
lpr('Creating release on GitHub');

// give github some time to have the new tag available
for ($i = 0; $i < 20; $i++) {
	echo '.';
	sleep(1);
}
lpr('.');

$url = "https://api.github.com/repos/{$reponame}/releases";
$vars = [
	"tag_name" => "v{$new_version}",
	"name" => "v{$new_version}",
	"body" => implode('', $release_notes),
	"draft" => false,
	"prerelease" => false,
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
	"Authorization: token {$user_token}",
	"User-Agent: PHP v" . phpversion(),
]);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($vars));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
curl_close($ch);

lpr('Finished Release', true);

/**
 * Helper functions
 */

/**
 * Print a line
 *
 * @param string $string the string to print
 * @param bool   $exit   exit the script after print (default: false)
 *
 * @return void
 */
function lpr($string, $exit = false) {
	echo $string;
	
	if ($exit) {
		exit();
	}
	
	echo PHP_EOL;
}

/**
 * Ask a question to the user
 *
 * @param string $question the question to ask
 *
 * @return string
 */
function ask($question) {
	lpr($question);
	return trim(fgets(STDIN));
}

/**
 * Generate release notes from github commits
 *
 * @param string $latest_tag the tag to get the commits after that
 *
 * @return false|string[]
 */
function generate_release_notes($latest_tag) {
	$latest_tag = trim($latest_tag);
	if (!empty($latest_tag)) {
		$latest_tag = "{$latest_tag}..HEAD ";
	}
	
	$counter = [
		'added' => 0,
		'changed' => 0,
		'fixed' => 0,
		'removed' => 0,
		'chore' => 0,
		'misc' => 0,
	];
	
	$command = "git log {$latest_tag}--format=format:\"%s\" --no-decorate --no-merges";

	$log = trim(shell_exec($command));
	if (empty($log)) {
		return false;
	}
	
	$commit_lines = preg_split("/\\r\\n|\\r|\\n/", $log);
	
	$output = [];
	foreach ($commit_lines as $key => $line) {
		$line = trim($line);
		if (stripos($line, 'chore') === 0) {
			$counter['chore']++;
			continue;
		} elseif (stripos($line, 'add') === 0) {
			$counter['added']++;
		} elseif (stripos($line, 'change') === 0) {
			$counter['changed']++;
		} elseif (stripos($line, 'fix') === 0) {
			$counter['fixed']++;
		} elseif (stripos($line, 'remove') === 0) {
			$counter['removed']++;
		} else {
			$counter['misc']++;
		}
		
		$output[] = "- $line" . PHP_EOL;
	}
	natcasesort($output);

	return [
		'notes' => $output,
		'counter' => $counter,
	];
}

/**
 * Get the github repo name currently working on
 *
 * @return false|string
 */
function get_repo_name() {
	$regex = '/\\s*Fetch URL:.*[:\\/]([\\w]*\\/[\\w]*)\\.git$/m';
	
	$command = "git remote show origin";

	$info = trim(shell_exec($command));
	preg_match($regex, $info, $matches);
	if (isset($matches[1])) {
		return $matches[1];
	} else {
		lpr($info);
		return false;
	}
}

/**
 * Get the Github api token
 *
 * @return false|string
 */
function get_github_token() {
	
	$files = get_github_token_files();
	if (empty($files)) {
		return false;
	}
	
	$filename = false;
	if (count($files) === 1) {
		$filename = $files[0];
	} else {
		$configname = ask('What is the config file to be used?');
		if (empty($configname) || !in_array($configname, $files)) {
			lpr('Missing user config file in: ' . dirname(__FILE__) . '/github_token/', true);
			return false;
		}
		
		$filename = $configname;
	}
	
	$content = file_get_contents(dirname(__FILE__) . '/github_token/' . $filename);
	if (empty($content)) {
		lpr('No user token found in: ' . dirname(__FILE__) . '/github_token/' . $filename, true);
		return false;
	}
	
	return $content;
}

/**
 * Get all available Github token files
 *
 * @return string[]
 */
function get_github_token_files() {
	
	$di = new DirectoryIterator(dirname(__FILE__) . '/github_token/');
	$files = [];
	/* @var $fileInfo SplFileInfo */
	foreach ($di as $fileInfo) {
		if (!$fileInfo->isFile()) {
			continue;
		}
		
		$files[] = $fileInfo->getBasename();
	}
	
	return $files;
}
