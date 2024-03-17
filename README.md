


# Laravel Microstrategy Integration


An API that connects to the Microstrategy Library API to fetch report results.

## Version Notes
This is still an incomplete version of the lib, API. Error returns from requests in case of problems with the request are not yet 100% developed, and the headers and metrics of the reports are still not returned in the getInstanceNoPrompt and getReport functions.






## Installation

To install this library in your Laravel project, you can run the following Composer command:

```bash
composer require JaimeSantosWeb/laravelmicrostrategyintegration
```

After installation, you will need to configure the authentication credentials.

To do this, publish the configuration file:
```bash
php artisan vendor:publish --tag=laravelmicrostrategyintegration
```

and then change the endpoint in the generated file  /config/strategy.php

After installation, you can configure the authentication credentials in your .env environment file.

## Usage Examples

AHere are some examples of how you can use this library in your Laravel project:

use App\LaravelMicrostrategyIntegration\Controllers\StrategyController;
### With Prompt


```bash
<?php

namespace App\Http\Controllers;

use App\LaravelMicrostrategyIntegration\Controllers\StrategyController;

class SUACLASSE 
{
    
        $reportId="<<Report ID>>";
        $projectID="<<Project ID>>"
        $prompt = file_get_contents('prompts/prompt.json', FILE_USE_INCLUDE_PATH);

        $strategy = new StrategyController($projectID);
        $strategy->Sauth($usuario,$senha);
        $instanceID= $strategy->getInstance($reportId);
        $strategy->sendPrompt($reportId,$instanceID,$prompt);
        $instanceID= $strategy->getReport($reportId, $instanceID);
        $strategy->logout();

       
        return view('welcome', compact('instanceID'));

}
```

### Without Prompt

```bash
<?php

namespace App\Http\Controllers;

use App\LaravelMicrostrategyIntegration\Controllers\StrategyController;

class SUACLASSE 
{
    
        $reportId="<<Report ID>>";
        $projectID="<<Project ID>>"
        $prompt = file_get_contents('prompts/prompt.json', FILE_USE_INCLUDE_PATH);

        $strategy = new StrategyController($projectID);
        $strategy->Sauth($usuario,$senha);
        $instanceID= $strategy->getInstanceNoPrompt($reportId);
        $strategy->logout();

       
        return view('welcome', compact('instanceID'));

}
```

## Configuration
Before using this library, make sure to configure the following environment variables in your .env file:

- ** MICROSTRATEGY_API_USERNAME=seu_usuario
- ** MICROSTRATEGY_API_PASSWORD=sua_senha
- ** MICROSTRATEGY_PROJECT_ID=seu_projeto_id


Replace the values your_username, your_password, and your_project_id with the corresponding values from your Microstrategy API instance. This way, you can use them within the project.

## Facilitating Data Reading
It is highly recommended to transform the data into JSON format before working with it in any instance. To do this, encode the functions' return values to JSON:

```bash
json_encode($instanceID)
```
## Dependencias

This library depends on the following libraries and Laravel versions:

- ** PHP >= 8.1
- ** Laravel Framework >= 10.10
- ** GuzzleHTTP >= 7.2


