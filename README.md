<h1>Bank Reconciliation System </h1>
This is my test result for BE PHP position.

**Table of content**
- [Flowchart](#flowchart)
- [Installation](#installation)
- [Usage](#usage)
- [Unit test](#unit-test)
- [Packages](#packages)
- [Notes](#notes)

## Flowchart
![BRS Flowchart](image.png)

## Installation
`1.` Make sure you are using a PHP version compatible with laravel 8 (Min: PHP 7.3). Tested on PHP 7.4.33.\
`2.` Install composer at [https://getcomposer.org](https://getcomposer.org/).\
`3.` Run ```composer update```.\
`4.` Rename ```.env.example``` to ```.env``` then run ```php artisan key:generate``` to create application key.\
`5.` Change your database config in ```.env```. You need to change ```QUEUE_CONNECTION``` from ```sync``` to ```database``` too.\
`6.` If you already have a database, the necessary tables or choose to import from the provided sql file, you can skip from step 7 to step 9.\
`7.` Run ```php artisan make:database YOUR_DB_NAME```. This command will create a new database.\
`8.` Run ```php artisan migrate```. This command will create some necessary tables.\
`9.` If you want to create some test users, run ```php artisan db:seed --class=UsersSeeder```. These users can be found in table ```users``` and default password is ```test2024```.\
`10.` Run ```php artisan jwt:secret``` to create JWT_SECRET in your ```.env``` file.\
`11.` To run your application, please excute command ```php artisan serve```. This command will return to you a local address.\
`12.` Run the worker: ```php artisan queue:work```

## Usage
`1.` Get token by send a post request with some configs as bellow:\
`-` CURLOPT_URL: ```your_host:your_port/api/auth/login```\
`-` CURLOPT_RETURNTRANSFER: ```true```\
`-` CURLOPT_ENCODING: ```""```\
`-` CURLOPT_MAXREDIRS: ```10```\
`-` CURLOPT_TIMEOUT: ```30```\
`-` CURLOPT_HTTP_VERSION: ```CURL_HTTP_VERSION_1_1```\
`-` CURLOPT_CUSTOMREQUEST: ```POST```\
`-` CURLOPT_POSTFIELDS : ```['email': YOUR_EMAIL,'password': YOUR_PASSWORD]```\
`-` CURLOPT_HTTPHEADER : ```['Accept: application/json','Content-type:  multipart/form-data]'```

The complete code with PHP looks like this:
```
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://127.0.0.1:8000/api/auth/login',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => ['email' => 'test2024@brs.com','password' => 'test2024'],
  CURLOPT_HTTPHEADER => ['Accept: application/json','Content-type:  multipart/form-data']
));

$response = curl_exec($curl);

curl_close($curl);
```
`2.` Call transaction service by send a request with some configs as bellow:\
`-` CURLOPT_URL: ```your_host:your_port/api/import```\
`-` CURLOPT_RETURNTRANSFER: ```true```\
`-` CURLOPT_ENCODING: ```""```\
`-` CURLOPT_MAXREDIRS: ```10```\
`-` CURLOPT_TIMEOUT: ```30```\
`-` CURLOPT_HTTP_VERSION: ```CURL_HTTP_VERSION_1_1```\
`-` CURLOPT_CUSTOMREQUEST: ```POST```\
`-` CURLOPT_POSTFIELDS : ```['file': YOUR_CURL_FILE]```\
`-` CURLOPT_HTTPHEADER : ```['Accept: application/json','Content-type: multipart/form-data','Authorization: Bearer YOUR_TOKEN']```\

The complete code with PHP looks like this:
```
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://127.0.0.1:8000/api/import',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => ['file'=> new CURLFILE('D:/xampp/htdocs/BRS/public/files/bank1k.csv')],
  CURLOPT_HTTPHEADER => ['Accept: application/json','Content-type:  multipart/form-data','Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTcyMTk3OTE0MSwiZXhwIjoxNzIxOTgyNzQxLCJuYmYiOjE3MjE5NzkxNDEsImp0aSI6IkVSeGNNV3BIZHRqaUZBaFIiLCJzdWIiOjEsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.vB_PVRg6WC8Wu0pDHuU_wPELlMYB2FRaDz1IRH9nuPo']
));

$response = curl_exec($curl);

curl_close($curl);
```
## Unit test
Use command ```php artisan test``` to run unit test

## Packages
`1.` JWT : [https://jwt-auth.readthedocs.io/en/develop/](https://jwt-auth.readthedocs.io/en/develop/)\
`2.` PHPSpreadsheet : [https://phpspreadsheet.readthedocs.io/en/latest/](https://phpspreadsheet.readthedocs.io/en/latest/)

## Notes
`1.` If you choose to import tables from the provided sql file. You can find them in ```/storage/sql/brs.sql```.\
`2.` If you dicide to upload a large file, you need to increase ```upload_max_size``` and ```post_max_size```.


