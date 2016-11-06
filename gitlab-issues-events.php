<?php

require __DIR__ . '/vendor/autoload.php';

use Curl\Curl;
use Ramsey\Uuid\Uuid;

ini_set('log_errors', true);
ini_set('error_log', __DIR__ . '/php-errors.log');

// mapping of GitLab path_with_namespace to Todoist project_id
$projects = [
	'gitlabuser/reponame' => 1234567890,
];

// set label ids here (JSON format)
$labels = '[12345678]';

// Enter GitLab Security Token here
define ('GITLAB_SECRET_TOKEN', 'your Gitlab secret token');

// Enter Todoist API Token
define ('TODOIST_API_TOKEN', 'your Todoist API token');

// Get GitLab Security Token - see additional infomation section in README.md for details
$GitlabClientToken = $_COOKIE['token'];

// check if Security Token is valid
if ($GitlabClientToken !== GITLAB_SECRET_TOKEN) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

// read json data from GitLab
$GitlabJson = file_get_contents('php://input');
$GitlabData = json_decode($GitlabJson, true);

// get project
$GitLabProject = $GitlabData['project']['path_with_namespace'];
$TodoistProjectId = ($projects[$GitLabProject]) ? $projects[$GitLabProject] : '';

// get issue data
$GitlabObjectAttributes = $GitlabData['object_attributes'];

// temp_id for Todoist
$TodoistTempId = Uuid::uuid4()->toString();

if ($GitlabObjectAttributes['action'] === 'open' || $GitlabObjectAttributes['action'] === 'reopen') {
	// todo: if reopen, find task and uncomplete it instead of creating a new task
	$TodoistContent = '[#' . $GitlabObjectAttributes['iid'] . '] '.$GitlabObjectAttributes['title'];
	if ($GitlabObjectAttributes['due_date'] != null) {
		$TodoistDateString = $GitlabObjectAttributes['due_date'];
		$TodoistDateLang = 'en';
	}
	else {
		$TodoistDateString = '';
		$TodoistDateLang = '';
	}

	// send to Todoist
	$commands = array(
		array(
			'type' => 'item_add',
			'temp_id' => $TodoistTempId,
			'uuid' => Uuid::uuid4()->toString(),
			'args' => array(
				'content' => $TodoistContent,
				'labels' => $labels,
				'project_id' => $TodoistProjectId,
				'date_string' => $TodoistDateString,
				'date_lang' => $TodoistDateLang
			)
		),
		array(
			'type' => 'note_add',
			'temp_id' => Uuid::uuid4()->toString(),
			'uuid' => Uuid::uuid4()->toString(),
			'args' => array(
				'item_id' => $TodoistTempId,
				'content' => $GitlabObjectAttributes['url'] . ' (GitLab Issue #'. $GitlabObjectAttributes['iid'] .')'
			)
		)
	);

	$curl = new Curl();
	$curl->put('https://todoist.com/API/v7/sync', array(
		'token' => TODOIST_API_TOKEN,
		'commands' => json_encode($commands)
	));
}
elseif ($GitlabObjectAttributes['action'] === 'close') {
	// use GitLab issue number to identify corresponding Todoist task
	$GitlabId = '[#' . $GitlabObjectAttributes['iid'] . ']';

	$TodoistItemId = getTodoistItem($GitlabId, $TodoistProjectId);

	if (is_integer($TodoistItemId) && $TodoistItemId > 0) {
		$commands = array(
			array(
				'type' => 'item_complete',
				'uuid' => Uuid::uuid4()->toString(),
				'args' => array(
					'ids' => array(
						$TodoistItemId
					)
				)
			)
		);

		$curl = new Curl();
		$curl->put('https://todoist.com/API/v7/sync', array(
			'token' => TODOIST_API_TOKEN,
			'commands' => json_encode($commands)
		));
	}
}
elseif ($GitlabObjectAttributes['action'] === 'update') {
	// use GitLab issue number to identify corresponding Todoist task
	$GitlabId = '[#' . $GitlabObjectAttributes['iid'] . ']';

	$TodoistItemId = getTodoistItem($GitlabId, $TodoistProjectId);

	if (is_integer($TodoistItemId) && $TodoistItemId > 0) {
		if ($GitlabObjectAttributes['due_date'] != null) {
			$TodoistDateString = $GitlabObjectAttributes['due_date'];
			$TodoistDateLang = 'en';
		}
		else {
			$TodoistDateString = '';
			$TodoistDateLang = '';
		}

		$commands = array(
			array(
				'type' => 'item_update',
				'uuid' => Uuid::uuid4()->toString(),
				'args' => array(
					'id' => $TodoistItemId,
					'date_string' => $TodoistDateString,
					'date_lang' => $TodoistDateLang
				)
			)
		);

		$curl = new Curl();
		$curl->put('https://todoist.com/API/v7/sync', array(
			'token' => TODOIST_API_TOKEN,
			'commands' => json_encode($commands)
		));
	}
}

/**
 * @param $GitlabIssueId
 * @param $TodoistProjectId
 * @return integer
 */
function getTodoistItem($GitlabIssueId, $TodoistProjectId) {
	// get all tasks from Todoist
	$curl = new Curl();
	$curl->get('https://todoist.com/API/v7/sync', array(
		'token' => TODOIST_API_TOKEN,
		'sync_token' => '*',
		'resource_types' => '["items"]'
	));

	if (!$curl->error) {
		$TodoistItems = $curl->response->items;

		foreach ($TodoistItems as $TodoistItem) {
			if (strpos($TodoistItem->content, $GitlabIssueId) === 0 && $TodoistItem->project_id === $TodoistProjectId) {
				return (integer) $TodoistItem->id;
			}
		}
	}

	return 0;
}
