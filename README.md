# extract-hooks

This script is intended for WordPress plugins that themselves provide hooks that can be used by other plugins and create a documentation in a Github wiki for them.

Typically, you'd first create a [`extract-hooks.ini`](https://github.com/akirk/extract-hooks/blob/main/extract-hooks.ini), and check out the Github wiki in a folder above the repo. Modify the `extract-hooks.ini` accordingly and execute extract-hooks.php. This will create markdown files in the wiki folder. You can then `git commit` and `git push` the changes.

Examples:
- [https://github.com/akirk/friends/wiki/Hooks](https://github.com/akirk/friends/wiki/Hooks)
- [https://github.com/akirk/enable-mastodon-apps/wiki/Hooks](https://github.com/akirk/enable-mastodon-apps/wiki/Hooks)
