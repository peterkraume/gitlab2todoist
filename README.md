# Sync GitLab issues to Todoist

## Description

Each new issue in GitLab triggers a new task in Todist. Upon closing an issue in GitLab the corresponding Todoist task is closed as well. When an issue is updated and there is a due date set, the corresponding Todoist task will receive the due date as well.

For the moment, there is no check of the assignee, so all new issues in GitLab are added to your Todoist list.

## Installation

1. Run `composer install`
2. Enter URL to Webhook section in GitLab project settings
   - Trigger "Issues events"
   - create Secret Token
3. Edit `gitlab-issues-events.php` and set mapping for project and label and add secret token

To get the project ID of the Todoist project, you can use this curl command:

```
curl https://todoist.com/API/v7/sync \
    -d token=YOUR_TODOIST_API_TOKEN \
    -d sync_token='*' \
    -d resource_types='["projects"]'
```

The website [jsonparseronline.com](http://jsonparseronline.com/) might be useful to parse the JSON result.

## Usage

- Create your first issue in GitLab and watch it to appear automagically in Todoist
- When you close an issue in GitLab, the corresponding Todoist task will be closed as well
- Set a new due date to an issue in Gitlab and the corresponding Todoist task will get the same due date

## Additional information

The `.htaccess` file is necessary for my hoster because the PHP installation doesn't support the X-Gitlab-Token header.

## MIT License

Copyright (c) 2016 [Peter Kraume](https://github.com/peterkraume) [(@cybersmog)](https://twitter.com/cybersmog)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.