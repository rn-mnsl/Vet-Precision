@echo off
echo Creating Vet Precision directory structure...

REM Create directories
mkdir config includes assets\css assets\js assets\images database uploads\pets
mkdir staff\appointments staff\pets staff\owners staff\medical staff\reports  
mkdir client\pets client\appointments client\medical client\profile
mkdir api\appointments api\pets api\auth

REM Create empty PHP files
echo. > index.php
echo. > login.php
echo. > register.php
echo. > logout.php

REM Create config files
echo. > config\database.php
echo. > config\constants.php
echo. > config\init.php

REM Create includes
echo. > includes\functions.php
echo. > includes\auth.php
echo. > includes\validation.php
echo. > includes\header.php
echo. > includes\footer.php
echo. > includes\navbar.php

echo Structure created successfully!
pause