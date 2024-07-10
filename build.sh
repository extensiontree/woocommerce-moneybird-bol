#!/bin/bash
# This script builds woocommerce-moneybird-bol.zip

EXTNAME="woocommerce-moneybird-bol"
EXTROOT=$PWD

# Check version tagging
if [ $# -eq 0 ]
then
    MOST_RECENT_TAG=`git describe --tags --abbrev=0 | cut -c2-`
    if grep -Fq "Version: $MOST_RECENT_TAG" ./$EXTNAME.php
    then
        echo "- Latest version tag found in plugin header!"
    else
        echo "Latest version tag NOT found in plugin header!"
        exit 1
    fi
fi

# Clean temporary files
rm -f ./*~

# Collect files
rm -f $EXTROOT/$EXTNAME.zip
rm -f -r /tmp/$EXTNAME
mkdir /tmp/$EXTNAME

cp ./*.md /tmp/$EXTNAME/
cp ./*.php /tmp/$EXTNAME/
cp -r plugin-update-checker /tmp/$EXTNAME/

# Create zip
cd /tmp; zip -r -q $EXTROOT/$EXTNAME.zip $EXTNAME

# Clean up
rm -f -r /tmp/$EXTNAME

echo "All done."
