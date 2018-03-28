CAS Server
==

Base on Laravel 5.2.45

DIRECTORY STRUCTURE
-------------------

      app/             		CAS的主要实现代码
      bootstrap/            应用启动脚本
      config/             	配置文件
      database/        		数据库迁移文件
      public/               经过 gulp发布后的
      resources/            静态资源文件以及其它资源包，如CSS，Js，语言，视图文件
      storage					
      tests/              
      vendor/                Laravel框架核心类以及其它第三方库
    


说明
------------

### 前端视图的数据绑定
Vue v1.0

### 前端静态资源编译
Elixir

### 核心代码

App\Auth\Cas\CasSessionGuard.php
App\Auth\Cas\CasUserProvider.php