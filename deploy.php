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
add('writable_dirs', ['bootstrap/cache']);


// Hosts
host('oeaudio.com')
    ->user('root')
    ->set('deploy_path', '/var/www/{{application}}')
    ->stage('staging')
    ->identityFile('~/.ssh/deployerkey');

// Tasks
task('build', function () {
    run('cd {{release_path}} && build');
});

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.

before('deploy:symlink', 'artisan:migrate');

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
    'artisan:cache:clear',
    'artisan:view:cache',
    'artisan:config:cache',
    'artisan:optimize:clear',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);

task('reload:php-fpm', function () {
    run('sudo systemctl restart php7.3-fpm');
});

after('deploy', 'reload:php-fpm');

// [Optional] If deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

task('deploy:shared', function () {
    $sharedPath = "{{deploy_path}}/shared";
    // Validate shared_dir, find duplicates
    foreach (get('shared_dirs') as $a) {
        foreach (get('shared_dirs') as $b) {
            if ($a !== $b && strpos(rtrim($a, '/') . '/', rtrim($b, '/') . '/') === 0) {
                throw new Exception("Can not share same dirs `$a` and `$b`.");
            }
        }
    }
    foreach (get('shared_dirs') as $dir) {
        // Check if shared dir does not exist.
        if (!test("[ -d $sharedPath/$dir ]")) {
            // Create shared dir if it does not exist.
            run("mkdir -p $sharedPath/$dir");
            // If release contains shared dir, copy that dir from release to shared.
            if (test("[ -d $(echo {{release_path}}/$dir) ]")) {
                run("cp -rv {{release_path}}/$dir $sharedPath/" . dirname(parse($dir)));
            }
        }
        // Remove from source.
        run("rm -rf {{release_path}}/$dir");
        // Create path to shared dir in release dir if it does not exist.
        // Symlink will not create the path and will fail otherwise.
        run("mkdir -p `dirname {{release_path}}/$dir`");
        // Symlink shared dir to release dir
        run("{{bin/symlink}} $sharedPath/$dir {{release_path}}/$dir");
    }
    foreach (get('shared_files') as $file) {
        $dirname = dirname(parse($file));
        // Create dir of shared file
        run("mkdir -p $sharedPath/" . $dirname);
        // Check if shared file does not exist in shared.
        // and file exist in release
        if (!test("[ -f $sharedPath/$file ]") && test("[ -f {{release_path}}/$file ]")) {
            // Copy file in shared dir if not present
            run("cp -rv {{release_path}}/$file $sharedPath/$file");
        }
        // Remove from source.
        run("if [ -f $(echo {{release_path}}/$file) ]; then rm -rf {{release_path}}/$file; fi");
        // Ensure dir is available in release
        run("if [ ! -d $(echo {{release_path}}/$dirname) ]; then mkdir -p {{release_path}}/$dirname;fi");
        // Touch shared
        run("touch $sharedPath/$file");
        // Symlink shared dir to release dir
        run("{{bin/symlink}} $sharedPath/$file {{release_path}}/$file");
    }
});
