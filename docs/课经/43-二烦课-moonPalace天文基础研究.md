# 第43课二烦课：moonPalace 天文基础研究验证

## 范围

本轮只验证“月宿”所需的月球真实赤经与传统十二宫映射，不实现 `ErfanRule`，也不接入生产排盘。工具使用地心月球，不使用北京经纬度或站心视差；输入必须自带时区并立即转换为 UTC。

## 恒星与坐标资料

虚宿距星固定为虚宿一，即 β Aquarii / Sadalsuud / HIP 106278。采用 CDS SIMBAD 对 HIP 106278 给出的 ICRS/J2000 星表位置：

- J2000 RA：`21h 31m 33.53207s`，即 `322.8897169583333°`；
- J2000 Dec：`-05° 34′ 16.22938″`，即约 `-5.5711748277778°`；
- 可核验来源：<https://simbad.cds.unistra.fr/simbad/sim-id?Ident=HIP+106278>。

当前版本没有应用恒星自行，工具明确输出 `proper_motion_applied: false`。β Aqr 在 2026 年附近的几十年尺度上由自行带来的位置变化远小于本研究的宫界宽度；但跨数百年的历史回算不能继续忽略，后续必须根据星表字段定义核验并加入自行、视差及需要时的径向速度，不能猜测参数语义。

研究依赖固定为官方 `astronomy-engine` `2.1.19`，许可证为 MIT；包信息及上游源码见 <https://www.npmjs.com/package/astronomy-engine/v/2.1.19> 与 <https://github.com/cosinekitty/astronomy>。

## 算法口径

Astronomy Engine `GeoMoon` 产生月球中心相对地心的 EQJ 向量。月球向量与由上述 J2000 RA/Dec 构造的恒星向量，都使用同一时刻的 `Rotation_EQJ_EQD` 转成 EQD，再由 `EquatorFromVector` 取得赤经。因而比较双方处于同一赤道日期坐标系。

古周天 `365.2578` 度，六古度换算为：

```text
xuSixModernDeg = 6 × 360 / 365.2578
ziCenterRa = normalize(xuRaEqd + xuSixModernDeg)
delta = normalize(moonRaEqd - ziCenterRa)
```

十二宫均宽 30°，子宫采用半开区间 `[ziCenterRa - 15°, ziCenterRa + 15°)`。赤经增加方向为 `子 → 亥 → 戌 → 酉 → 申 → 未 → 午 → 巳 → 辰 → 卯 → 寅 → 丑`，恰与项目地支索引增加方向相反。所有对外角度归一化到 `[0, 360)`。

## 使用与边界搜索

```bash
node tools/moon_palace.mjs "2026-09-13T01:10:00+08:00"
node tools/moon_palace_boundary.mjs "2026-09-13T01:10:00+08:00"
npm run test:moon-palace
```

边界搜索先按一小时步长找到宫位变化区间，再二分夹逼；默认报告的时间容差为 100 ms。该逻辑只用于比较不同天文实现的交宫时间，不属于生产接口。

## 已知精度限制

- Astronomy Engine 的月球模型及其时间尺度、岁差章动实现决定基础星历精度；本研究 fixture 只用于发现代码漂移，不是独立天文真值。
- β Aqr 暂未应用自行；2026 附近影响极小，历史长时段回算需补齐。
- 宫界取决于冻结的“虚六度”与古周天换算口径；它不是现代回归黄道的 30° 星座分区。
- 边界附近任何天文实现的小角度差都会变成交宫时间差，所以生产选型前应与第二个独立实现比较。
- 《大全》二烦正文例没有明确年份，不能用于声称现代星历端到端复现“月宿卯宫”。
