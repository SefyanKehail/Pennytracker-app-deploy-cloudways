<h1>PennyTracker</h1>

<h2 href="#introduction">Introduction</h2>

<p>
    This project was developed using SLIM, a Micro PHP Framework, As it serves the purpose, and It's more than enough for the
    size of this project. The goal was to write a clean code and production-ready application.
    It's not as sophisticated as Symfony since you'll have to require most of the stuff you need and manually structure
    your code.
    But I tried to follow good code conventions as much as possible.<br/>
    The app(PennyTracker) is a solution for managing and monitoring your finances. Providing the user with insightful
    statistics.
    It helps you monitor and track your transactions over the year, analyzing your incomes, expenses and spending
    patterns.
    You can manually input transactions directly into the app, providing details such as amount, category, and date.
    Alternatively, you can bulk-upload transactions by importing CSV files.
    Organize your transactions efficiently by categorizing them into relevant groups.
    In addition to customizable time filters, you can analyze your financial data over specific periods using
    PennyTracker's statistics and visual charts.
</p>

<h2>Table of Contents</h2>
<ul>
    <li><a href="#introduction">Introduction</a></li>
    <li><a href="#project-structure">Project Structure</a></li>
    <li><a href="#env">Development Environment</a></li>
    <li><a href="#backend-frontend">Backend/ Frontend</a></li>
    <li><a href="#installation">Installation</a></li>
    <li><a href="#deployment">deployment</a></li>
    <li><a href="#key-points">Key Points</a></li>
    <li><a href="#issues">issues</a></li>
</ul>

<h2 href="#project-structure">Project Structure</h2>

<p>
    Describe the structure of your project here. Explain how different components/modules are organized and their
    purposes.
</p>

<pre>
project/
│
├── app/                   # PHP source code
│   ├── Controllers/       # Controllers
│   ├── Contracts/         # Interfaces
│   ├── Commands/          # Custom console commands
│   ├── Middleware/        # Middleware
│   ├── DTO/               # Wrappers
│   ├── Enums/             # Enums
│   ├── Entity/            # ORM Entities
│   ├── Exceptions/        # Exceptions
│   ├── Filters/           # Doctrine Filters
│   ├── Mailing/           # Mailing classes
│   ├── Services/          # Controllers
│   ├── Validators/        # Input Validators
│   └── Auth.php           # Main authentication class
│   └── Config.php         # Config class that gets configs from the configs array by keys
│   └── Session.php        # Sessions wrapper
│   └── Auth.php           # Main authentication class│
│
│
├── configs/                           # Configuration files
│   ├── commands/                      # each php file returns an array of commands as objects
│   ├── container/                     # container configs
│   │    ├── container_bindings        # returns an array of container bindings
│   │    └── container.php             # takes the array above and returns a DI Container instance.
│   │
│   ├── routes/                        # contains main routing file
│   ├── app.php                        # where all app settings are stored, and can be retrieved via Config class
│   ├── middleware.php                 # where middlewares can be added
│   ├── path_constants.php             # Path constants definitions
│   └── ...
│
├── docker/                         # Docker folder
│   ├── nginx/                      # Nginx config file
│   ├── storage/                    # Docker volumes
│   ├── Dockerfile                  # PHP environment container
│   ├── docker-compose.yml          # Containers
│   ├── xdebug.ini                  # Xdebug file
│   └── local.ini                   # PHP config file
│
├── resources/            # App resources
│   ├── css/              # scss files
│   ├── images/           # images
│   ├── js/               # javascript files
│   └── views/            # twig views
│
│
├── public/               # Web root
│   ├── index.php         # Entry point
│   └── build             # Build files
│
├── tests/                  # Unit tests folder ( empty for now :p )
│   └── ...
│
├── .env.example                # ENV file params
├── bootstrap.php               # The file that bootstraps my app ( autoloader, dotenv..) returns container instance
├── pennytracker                # PHP console file ( gets container instance from bootstrap file and commands arrays )
├── webpack.configs.js          # Symfony webpack encore configuration
├── package.json                # NPM dependencies
└── composer.json               # Composer dependencies

</pre>

<h2 href="#env">Development Environment</h2>
<ul>
    <li><code>Docker/Docker-compose</code>
    </li>
    <li><code>Coding inside WSL2:</code> Docker is such a pain when used with windows filesystem everything is very slow
        because Docker-desktop has to convert every action over to WSL and only then execute it. So coding directly
        inside linux decreases execution time by seconds.
    </li>
</ul>

<h2 href="#backend-frontend">Backend/ Frontend</h2>
<ul>
    <li><code>Nginx:</code> As my development server.</li>
    <li><code>PHP 8.1:</code> Backend</li>
    <li><code>Slim PHP framework:</code> Slim PHP v4</li>
    <li><code>Composer:</code> Manage PHP dependencies</li>
    <li><code>Mysql 8.0</code></li>
    <li><code>Doctrine:</code> ORM, Query Builder, Paginator</li>
    <li><code>Vulcas/Valitron (Validation):</code> Valitron is a validation library for PHP</li>
    <li><code>Symfony Mailer:</code> For 2FA emails, and account activation emails.</li>
    <li><code>Mailhog (for testing) / Mailtrap (for production):</code>SMTP Servers</li>
    <li><code>Flysystem (AWS S3 adapter for production):</code> I used DigitalOcean AWS services ( 200$ 2 months for
        free)
    </li>
    <li><code>Bootstrap V5</code></li>
    <li><code>Native JavaScript</code></li>
    <li><code>NodeJS NPM:</code> Manage JS packages</li>
    <li><code>Clockwork (Monitoring):</code> Helped a lot to identify slow SQL queries/memory leaks</li>
    <li><code>Symfony Webpack Encore:</code> For bundling my files</li>
    <li><code>Redis (Caching/RateLimiting):</code></li>
    <li><code>Xdebug</code>
    </li>
</ul>


<h2 href="#installation">Installation</h2>

<ol>
    <li>Inside docker/ build containers: <code>docker-compose up -d --build</code></li>
    <li>Bash to the main app container: <code>docker exec -it pennytracker-app bash</code></li>
    <li>Run composer: <code>composer install</code></li>
    <li>Run npm: <code>npm install/ npm run dev</code></li>
    <li>Copy .env.example .env and fill in (storage is either local or s3)</li>
    <li>Run migirations diff using our app console: <code>php pennytracker migrations:diff </code></li>
    <li>Execute the migration: <code>php pennytracker migrate</code></li>
    <li>Generate APP-KEY: <code>php pennytracker app:generate-key</code></li>
</ol>

<h2 href="#deployment">Deployment</h2>

<p>
    I deployed my app in Cloudways VPS. (why VPS? It allows Redis caching contrary to shared hosting + it's more
    secured)
</p>

<ol>
    <li>Set up a web server with PHP support.</li>
    <li>Configure the web server and SMTP, (AWS S3 storage is already working)</li>
    <li>Configure deployment via Git</li>
    <li>SSH to server</li>
    <li>SSH to server: <code>composer install/ npm install/ npm run build</code></li>
    <li>Set up .env file properly</li>
    <li>Generate APP-KEY: <code>php pennytracker app:generate-key</code></li>
</ol>

<h2 href="#key-points">Key points in this app</h2>

<ul>
    <li>Good coding conventions: (Separation of concerns/ Factory Design Pattern/ DI Container/ little refactoring never
        hurts ...).
    </li>
    <li>Protection against CSRF attacks using CSRF tokens.</li>
    <li>Proper file/input validation</li>
    <li>Redis caching</li>
    <li>Redis Rate Limiting</li>
    <li>Middleware driven structure</li>
    <li>Optimized database queries and memory (I used eager-loading for pagination queries, and Batch Processing for csv
        imports,
        while also clearing the association graph manually when needed)
    </li>
    <li>...</li>
</ul>

<h2 href="#issues">Possible Issues and solutions</h2>

<h3>Related to WSL</h3>
<p>The first issue you'll probably run into if you're using Docker-desktop for windows, and you're coding inside
    WSL is memory leaks
    so you'd probably set the max resources for WSL by creating a file (.wslconfig) in C:\Users\Username and set it like
    this:</p>
<code>[wsl2]
    memory=1GB # Limits VM memory in WSL 2. If you want less than 1GB, use something like 500MB, not 0.5GB
    processors=2 # Makes the WSL 2 VM use two virtual processors
</code>

<p>For linux permission issues</p>
<code>sudo chmod -R a+rwX .</code>

<h3>Related to Docker-desktop/ Docker</h3>

<p>You'd probably want to get the latest Docker-desktop update before setting up your linux distro (Ubuntu) with
    Docker-desktop
</p>

<p>If docker-compose fails to build containers try doing it with no cache instead: <code>docker-compose build --no-cache
</code>
</p>

<h3>Related to App container</h3>
<p>NPM Troubleshooting</p>
<ul><li>set up dedicated ram from WSL VM (give it the half)<code>NODE_OPTIONS="--max-old-space-size=512"</code></li></ul>
<ul><li>Update NPM<code>npm install -g npm@latest </code></li></ul>
<ul><li>Clear Cache<code>npm cache clean --force</code></li></ul>

<p>Migrations</p>
<ul><li>delete migrations in migrations/ folder if migrating up for the first time doesn't work</li></ul>

<h3>Extra (Generate CSV files for testing)</h3>
<p>
In added a python script in app/Python-utils to generate CSV files for transactions upload testing.
You can choose a custom number of lines, that is to say the maximum upload size is 5MB which is roughly around 77k lines max
</p>
