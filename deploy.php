<?php
namespace Deployer;

require 'recipe/laravel.php';

// Project name
set('application', 'haolaoshi');

// Project repository
set('repository', 'git@github.com:rachelroll/haolaoshi.git');
set('composer_options', '{{composer_action}} --verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader --ignore-platform-reqs
');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);
set('writable_mode', 'chown');

set('keep_releases', 2);

// Shared files/dirs between deploys
add('shared_files', []);
add('shared_dirs', []);

// Writable dirs by web server
add('writable_dirs', []);


// Hosts
host('oeaudio.com')
    ->user('root')
    ->set('deploy_path', '/var/www/{{application}}')
    ->stage('staging');

// Tasks

task('build', function () {
    run('cd {{release_path}} && build');
});

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');



// Migrate database before symlink new release.

before('deploy:symlink', 'artisan:migrate');

/**
 * Main task
 */
desc('Deploy your project');

task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'deploy:writable',
    'artisan:storage:link',
    'artisan:view:cache',
    'artisan:config:cache',
    'artisan:optimize:clear',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
]);


//task('deploy:upload', function () {
//    upload('build/', '{{release_path}}/public');
//});

task('reload:php-fpm', function () {
    run('service php7.2-fpm restart');
});

after('deploy', 'reload:php-fpm');

task('restart:supervisorctl', function () {
    run('supervisorctl restart all');
});

//after('deploy', 'restart:supervisorctl');

after('deploy', 'success');

