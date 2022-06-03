中文 | [English](./README.md)

# GazellePW

全称 GazellePosterWall，一个 PT（Private Tracker）Web 框架，Gazelle 的 **影视版本**。

## 背景

[WhatCD/Gazelle](https://github.com/WhatCD/Gazelle) 最初诞生于音乐站点，尽管后来 OPSnet 开发组在其基础上做了一些代码重构，也只是为其音乐内容锦上添花。而 Gazelle 的应用不止于此，我们基于 [OPSnet/Gazelle](https://github.com/OPSnet/Gazelle) 的某个版本，进行了大量的功能新增和逻辑优化，使 Gazelle 适用于电影站的建设，我们称其为 GazellePosterWall，而如果想要基于 GazellePW 搭建 TV 甚至是其他类别的站点，相较原版 Gazelle，也会更加容易。

## 特性

- 精美的界面：响应式布局，手机端界面适配，BBCode 工具栏，所有图标都是 SVG 格式等
- 主题: 自动明/暗色主题切换，一小时创建出一个新主题，基于组件的样式
- 影视优化：发布时自动获取影片信息，截图对比图(支持像素对比和曲线滤镜)，MediaInfo，海报墙，多版本槽位分类、搜索，种子槽位系统等
- 多语言支持：中英双语
- 图床：本地或者[Minio](https://github.com/minio/minio)
- 在免费和中性基础上，额外增加 25%，50%，75% 种子免费
- 现代化开发：Docker, Vite, React
- ...

## 文档

- [快速开始](docs/Getting-Started.md)
- [前端开发指南](docs/Frontend-Development-Guide.md)

## 参与贡献

我们非常欢迎来自社区的各种贡献！

- 通过 [Issues](https://github.com/Mosasauroidea/GazellePW/issues/new/choose) 报告 bug 或者提出功能需求
- 通过 [Pull requests](https://github.com/Mosasauroidea/GazellePW/pulls) 提交代码修改

## 特别鸣谢

- 所有开发人员和贡献者
- [TheMovieDB](https://www.themoviedb.org/)
- [OMDb API](https://www.omdbapi.com/)
- [imdbphp](https://github.com/tboothman/imdbphp)
- [WhatCD/Gazelle](https://github.com/WhatCD/Gazelle)
- [OPSnet/Gazelle](https://github.com/WhatCD/Gazelle)