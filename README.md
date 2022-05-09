## Step for install dpt

	1) Get the clone of dpt repo.

		git clone https://github.com/Wappnet-Systems/dpt_software_backend.git

	2) Run bellow cmd

		composer install

	3) Setup .env file

	4) Create database on adminer or phpmyadmin with the name of `dpt`

	5) Run bellow cmd

		php artisan migrate --seed

## Live DPT Project Url

	http://68.183.80.245/dpt_software/login

## Step for git commit

	Note : Please follow bellow stepe for commit the code.

	1) git status

	2) `git add .` (for add files to commit) or `git add {file name with full path}` (for add single file)

	3) git commit -m "{commit message}"

	4) `git checkout master` (get latest code of master branch into local master branch)

	5) `git pull origin master` (take a pull from master branch)

	6) `git checkout {working_branch}` (go to last working branch where we have commit the code)

	7) `git merge origin/master` (for merge master barach code into working branch code, if any files are conflict please resolved first)

	8) `git push origin {working_branch}` (for commit the code and generate pr for master branch)

## CMD for tenancy database seeding and migrations

	1) Create migration for tenancy database

		- php artisan make:migration create__table --path=/database/migrations/tenant

	2) Run mgration for tenancy database

		- php artisan tenancy:migrate

	3) Run seeder for tenancy database

		- php artisan tenancy:db:seed