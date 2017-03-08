# CHANGELOG

## next

- set job number for static content deploy to 16
- rename assets to artifacts to unify the naming here (update of magedeploy2.php needed)
- config:init can be run multiple times
- introduce environment variable usage (re-generate your config by using config:init)
- improve config writing: use template to generate basic file content
- improve config reading: fallback to default file if necessary
- remove default config from config object

## 1.2.0

- add artifacts_dir to upload packages from
- run git clone independently if repo has not been cloned

## 1.1.3

- add support for git branch and git tag detection
- optimize source-code update task
