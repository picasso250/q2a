update zhihu_user set salt =  SHA1(CONCAT(username,fetch_time))