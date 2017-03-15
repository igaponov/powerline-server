#! /bin/bash
#
#   This will rsync the necessary files into the build/dist/build_#
#   dir so that we can package it up into a deb
#
#   We use fpm to create the deb in the release dir which then
#   will be uploaded to the s3 bucket for deployment
#

BUILD_DIR=build/dist/${TRAVIS_BUILD_NUMBER}
REL_DIR=build/release/${TRAVIS_BUILD_NUMBER}
ARTIFACT=civix-apiserver_${TRAVIS_BUILD_NUMBER}_all.deb
ARTIFACT_HASH=$ARTIFACT.hash
LATEST=civix-apiserver_latest_all.deb
LATEST_HASH=$LATEST.hash

mkdir -p $BUILD_DIR
mkdir -p $REL_DIR

rsync -qavz \
    --include='app/***' \
    --include='bin/***' \
    --include='var/bootstrap.php.cache' \
    --exclude='src/Civix/*Bundle/Test*' \
    --include='src/***' \
    --exclude='web/app_test.php' \
    --include='web/***' \
    --include='vendor/***' \
    --include='composer.*' \
    --exclude='deployment/' \
    --exclude='*' \
    ./ $BUILD_DIR


# fpm settings
#
# fpm -s make from dir
#     -t package deb
#     -n package name
#     -a all=noarch
#     -v package version
#     src_dir=tgt_dir

fpm -s dir -t deb -n civix-apiserver -v $TRAVIS_BUILD_NUMBER -a all $BUILD_DIR=/srv/civix-apiserver/

if [[ ! -f $ARTIFACT ]]; then
  echo " ===> I seem to be missing ${ARTIFACT}. "
  echo " ===> Did FPM work correctly?"
  exit 1
fi

# move the new build to the build dir for upload
# and create hash
mv $ARTIFACT $REL_DIR
sha256sum $REL_DIR/$ARTIFACT > $REL_DIR/$ARTIFACT_HASH

# Create the latest build
# and create hash
cp $REL_DIR/$ARTIFACT $REL_DIR/$LATEST
sha256sum $REL_DIR/$LATEST > $REL_DIR/$LATEST_HASH

