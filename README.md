# repo-maker

# RepositoryMaker.php

This maker creates Doctrine Repositories either by letting you name them or by looping. You use this Maker like any other Symfony maker.

Looping will examine you Entities and current Repositories and will only suggest Repositories that do not exist already. 

This means it gathers a list of your Entities and compares them to a list of existing Repositories. This way you do not accidentally overwrite any existing Repositories.

If you used the naming version it CAN OVERWRITE your current repositories.

## How to use this

To use this create a directory named Maker inside your src directory. 

Then pull this repo to any directory and copy the RepositoryMaker.php file into the maker Directory. 

That is it. There is nothing else to do if you are using Symfony 5+ it will automatically find this file when you type php bin/console list make.

[Learn more about using this maker](https://wp.me/pbkva6-1qr)


