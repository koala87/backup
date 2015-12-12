1、pache配置web项目根目录，ubuntu下默认安装目录为/var/www/，将以下两个配置文件中的两处目录换成需要的目录，如/home/yqc/luffy/src/php_api，

文件：/etc/apache2/apache2.conf

#<Directory /var/www/>
<Directory /home/yqc/luffy/src/php_api/>
        Options Indexes FollowSymLinks
        AllowOverride None
        Require all granted
</Directory>


文件：/etc/apache2/sites-enabled/000-default.conf
<VirtualHost *:80>
        ServerAdmin webmaster@localhost
#       DocumentRoot /var/www/

        DocumentRoot /home/yqc/luffy/src/php_api/
  

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>


2、配置数据库、sphinx、redis。
	打开config.php文件设置对应项即可

3、确保config.php文件可写

4、确保Log目录可写权限

5、创建链接文件 01---》/media/disk/01 。。。。（从01到09依次创建）

6、执行tools文件夹中php_server_check.py脚本，查看web服务是否正常
