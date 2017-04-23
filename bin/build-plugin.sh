#!/bin/bash
# Build script for Eighty/20 Results - Annual Pricing Choice add-on for Paid Memberships Pro
#
#
# Copyright (C) 2016  Thomas Sjolshagen - Eighty / 20 Results by Wicked Strong Chicks, LLC
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
include=(css js languages LICENSES pages plugin-updates e20r-annual-pricing-choice.php readme.txt)
exclude=(*.yml *.phar composer.* vendor)
build=(plugin-updates/vendor/*.php)
short_name="e20r-annual-pricing-choice"
plugin_path="${short_name}"
version=$(egrep "^Version:" ../${short_name}.php | awk '{print $2}')
metadata="../metadata.json"
src_path="../"
dst_path="../build/${plugin_path}"
kit_path="../build/kits"
kit_name="${kit_path}/${short_name}-${version}"

echo "Building kit for version ${version}"

mkdir -p ${kit_path}
mkdir -p ${dst_path}

if [[ -f  ${kit_name} ]]
then
    echo "Kit is already present. Cleaning up"
    rm -rf ${dst_path}
    rm -f ${kit_name}
fi

for p in ${include[@]}; do
	cp -R ${src_path}${p} ${dst_path}
done

for e in ${exclude[@]}; do
    find ${dst_path} -type d -iname ${e} -exec rm -rf {} \;
done

mkdir -p ${dst_path}/plugin-updates/vendor/
for b in ${build[@]}; do
    cp ${src_path}${b} ${dst_path}/plugin-updates/vendor/
done

cd ${dst_path}/..
zip -r ${kit_name}.zip ${plugin_path}
ssh siteground-e20r "cd ./www/protected-content/ ; mkdir -p \"${short_name}\""
scp ${kit_name}.zip siteground-e20r:./www/protected-content/${short_name}/
scp ${metadata} siteground-e20r:./www/protected-content/${short_name}/
ssh siteground-e20r "cd ./www/protected-content/ ; ln -sf \"${short_name}\"/\"${short_name}\"-\"${version}\".zip \"${short_name}\".zip"
rm -rf ${dst_path}


