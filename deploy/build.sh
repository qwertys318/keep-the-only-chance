rm -rf build
mkdir build
cd ./build
git clone git@github.com:qwertys318/keep-the-only-chance.git .
git checkout "${1}"
mv .env.prod .env
composer install --no-dev --no-interaction --no-cache
composer dump-autoload -o
composer dump-env prod
rm -rf ./.git ./.env ./front
docker build -f ../deploy/Dockerfile -t docker.pkg.github.com/qwertys318/keep-the-only-chance/ktoc-fpm:${1} -t docker.pkg.github.com/qwertys318/keep-the-only-chance/ktoc-fpm:latest  .
docker push docker.pkg.github.com/qwertys318/keep-the-only-chance/ktoc-fpm:${1}
docker push docker.pkg.github.com/qwertys318/keep-the-only-chance/ktoc-fpm:latest