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

if [[ ! -z "$TRAVIS_TAG" ]]; then
    # tagged release for prod
    ARTIFACT=civix-apiserver_${TRAVIS_TAG}_all.deb
    PKG_VERSION=$TRAVIS_TAG
else
    # dev/staging builds
    ARTIFACT=civix-apiserver_${TRAVIS_BUILD_NUMBER}_all.deb
    PKG_VERSION=$TRAVIS_BUILD_NUMBER
fi

ARTIFACT_HASH=$ARTIFACT.hash

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


fpm -s dir -t deb -n civix-apiserver -a all -v $PKG_VERSION $BUILD_DIR=/srv/civix-apiserver/

if [[ ! -f $ARTIFACT ]]; then
  echo " ===> I seem to be missing ${ARTIFACT}. "
  echo " ===> Did FPM work correctly?"
  echo " ===> Here is what is in the build dir: "
  ls -l $BUILD_DIR
  exit 1
fi

# move the new build to the build dir for upload
# and create hash
mv $ARTIFACT $REL_DIR
sha256sum $REL_DIR/$ARTIFACT > $REL_DIR/$ARTIFACT_HASH
