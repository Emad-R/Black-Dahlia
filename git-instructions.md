# Github Instructions

## Step 0:

BEFORE following these steps ensure that you have:
- Git installed
- VSCode installed
- GitLens extension installed on VSCode

You also MAY need to run the following commands:

`git config --global user.name "username (Github name)`

`git config --global user.email "email (used for Github)"`

Ensure that git auto fetch is enabled by opening VSCode settings and changing `Git: Autofetch` to `enabled`

## Step 1: Initial Github Instructions

First, open the Original Github repo.
Run the git clone command:

`git clone <repo_url>` (clone repo)

`cd <repo_name>` (navigate to dir)

## Saving changes you have made using Git

Now you are ready to start working, so you must create a branch (before or after making changes):

`git checkout -b <branch_name>` (CREATES new branch)

`git checkout <branch_name>` (switches to branch

Use VSCode Source Control OR GitLens OR Terminal to stage and commit any changes 

`git add .` (Stage all changes within the dir)

`git add <file> <file> ...` (Stage only specific files)

`git commit -m "Commit message"` (Commit the staged changes and include description)

Once a commit is made, push it through TERMINAL THE FIRST TIME:

`git push -u origin <branch-name>` (For future pushes to the same branch)

After this first push, you may use Source Control, GitLens, or Terminal:

`git push origin <branch-name>` (Once -u command was already used)

In order to merge a branch into main, you must create a pull request and then merge on GitHub
Then you must update your repo to reflect main:

`git checkout main`

`git pull origin main`
