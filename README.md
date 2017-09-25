# Hummingbird Stack
A light-weight, modular and agile OOP PHP framework

## Credits
This framework uses the following libraries:

- [PHPass](http://www.openwall.com/phpass/): A commonly used library used for creating 1-way hashes for storing passwords
- [TwilioSDK for PHP](https://www.twilio.com/docs/libraries/php): A library used to integrate with Twilio. Primary used for SMS sending
- [PHPMailer](https://github.com/PHPMailer/PHPMailer): A library used to integrate with SMTP services for email sending
- [LibPhoneNumber for PHP](https://giggsey.com/libphonenumber/): A library used to validate and format phone numbers and give limited information
- [RedBean for PHP](https://redbeanphp.com/index.php): A library used to interact with several types of databases in a simple and unified format.
- [Elephant.io](https://github.com/Wisembly/elephant.io): Used to push information to socket.io websocket service

## How to Use
The hummingbird stack framework is best used as a GIT fork, so you can pull down from the master repository whenever you want the most up-to-date code.
For a more in-depth tutorial for how to do that, simply visit [Github's Guide on Forking](https://help.github.com/articles/fork-a-repo/)

## How it Works
In order to allow for fast and flexible development, the Hummingbird stack loads all modules inside of a "Core" class ( called HC ). From there, you can add and exection action hooks ( just like in WordPress development ), which allows us to ensure that all of the relevant code is loaded before we try to execute it.

Modules are created with the following structure:

```
/modules/{module}/
		/interfaces/
		/adapters/
		/data/
		/abstracts/
		/classes/
		/functions/
		/instructions.php
```

All of the folders and files listed above are optional, but if you want to plug functions into the correct loading slot, you should use the `instructions.php` file to use `$this->addAction()`

The default system actions which are called are:

- `init`: Used to load more functionality into the system.
- `initDatabases`: Used to initialize databases. Databases should be added here.
- `initRouting`: Used to initialize routing. This is where we can add or change routes.
- `shutdown`: Used during rendering results.

The hummingbird stack framework can work without any additional configuration, but for the best results, you should use a config array / file.

## Feedback

Hummingbird supports 4 methods of feedback:

- Plain Text
- HTML
- JSON
- XML