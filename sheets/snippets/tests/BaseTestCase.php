<?php
/**
 * Copyright 2018 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class BaseTestCase extends PHPUnit_Framework_TestCase {
  const APPLICATION_NAME = 'Google Sheets API Snippet Tests';

  protected static $service;
  protected static $driveService;
  protected $filesToDelete;

  public static function setUpBeforeClass() {
    $client = self::createClient();
    BaseTestCase::$service = new Google_Service_Sheets($client);
    BaseTestCase::$driveService = new Google_Service_Drive($client);
  }

  protected function setUp() {
    $this->filesToDelete = [];
    // Hide STDOUT output generated by snippets.
    ob_start();
  }

  protected function tearDown() {
    foreach ($this->filesToDelete as $fileId) {
      self::$driveService->files->delete($fileId);
    }
    // Restore STDOUT.
    ob_end_clean();
  }

  protected static function createClient() {
    // create a log channel
    $log = new Logger('debug');
    $client = new Google_Client();
    $client->setApplicationName(self::APPLICATION_NAME);
    $client->useApplicationDefaultCredentials();
    $client->addScope('https://www.googleapis.com/auth/drive');
    $client->setLogger($log);
    return $client;
  }

  protected function deleteFileOnCleanup($id) {
    array_push($this->filesToDelete, $id);
  }

  protected function createTestSpreadsheet() {
    $spreadsheet = new Google_Service_Sheets_Spreadsheet([
      'properties' => [
        'title' => 'Test Spreadsheet'
      ]
    ]);
    $spreadsheet = self::$service->spreadsheets->create($spreadsheet);
    $this->deleteFileOnCleanup($spreadsheet->spreadsheetId);
    return $spreadsheet->spreadsheetId;
  }

  protected function populateValues($spreadsheetId) {
    $requests = [
      'repeatCell' => [
        'range' => [
          'sheetId' => 0,
          'startRowIndex' => 0,
          'endRowIndex' => 10,
          'startColumnIndex' => 0,
          'endColumnIndex' => 10
        ],
        'cell' => [
          'userEnteredValue' => [
            'stringValue' => 'Hello'
          ]
        ],
        'fields' => 'userEnteredValue'
      ]
    ];
    $body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
      'requests' => $requests
    ]);
    self::$service->spreadsheets->batchUpdate($spreadsheetId, $body);
  }
}
