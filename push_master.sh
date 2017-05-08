# !/bin/sh
# 快速将dev合并到master并推送到远程库

# 更新dev到最新
git checkout develop
git pull origin develop
git push origin develop

# 合并dev到master
git checkout master
git pull origin master
git merge develop -m 'merge from dev'

# 将master推到origin
git push origin master

# 切换回dev
git checkout develop
