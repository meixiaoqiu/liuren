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

## 独立天文交叉验证

### 数据源与独立实现

交叉验证使用 NASA/JPL Horizons API，2026 年响应标明月球和地球状态来源均为 DE441。完整请求 URL、参数、API 签名和原始文本响应保存在 `tests/Fixtures/moon_palace_horizons_2026.json`。

Horizons 离散样本与边界网格统一使用：

```text
COMMAND='301'
CENTER='500@399'
EPHEM_TYPE='VECTORS'
REF_SYSTEM='ICRF'
REF_PLANE='FRAME'
VEC_CORR='NONE'
VEC_TABLE='2'
OUT_UNITS='KM-S'
TIME_TYPE='UT'
CAL_TYPE='GREGORIAN'
CSV_FORMAT='YES'
```

响应同时明确记载 `Center-site name: BODY CENTER`、`Output type: GEOMETRIC cartesian states` 和 `Reference frame: ICRF`，因此没有使用站心位置、光行时或恒星光行差修正。

独立坐标转换采用 pyerfa `2.0.1.5`（ERFA/SOFA 算法）及 NumPy `2.3.5`。UTC 经 ERFA 转 TAI、TT，随后以 `pnm06a`（IAU 2006 岁差、IAU 2000A 章动）将 Horizons ICRF 月球向量和 β Aqr ICRS/J2000 向量分别转到真赤道/真春分点日期坐标。独立脚本重新实现虚六度换算、角度归一化、十二宫半开区间和反向宫序，没有调用 `moon_palace_core.mjs` 的天文转换或宫位函数。

### 2026 年向量样本

样本共 20 个：已有 fixture 的 5 个固定 UTC 时刻，加上固定随机种子 `4301` 产生的 15 个全年 UTC 时刻。角距离以完整三维单位方向向量计算，不以 RA 差替代。

| 指标 | Astronomy Engine `GeoMoon` 对 Horizons DE441 |
| --- | ---: |
| 最大方向角误差 | 2.963216″ |
| 平均方向角误差 | 2.298682″ |
| 中位数 | 2.493653″ |
| 95百分位 | 2.936261″ |
| 最大绝对距离误差 | 12.551424 km |
| 平均有符号距离误差（AE − JPL） | -10.755339 km |

20 个样本的最终宫位全部一致。最大 Moon EQD RA 差为 `3.304910″`，最大 Xu EQD RA 差和最大子宫中心差均为 `0.139295″`。

### 原有五个 fixture 时刻

下表差值均为绝对角秒；“界距差”是最近宫界距离之差。

| UTC | Moon RA差 | Xu RA差 | 子中心差 | delta差 | 界距差 | 宫位 |
| --- | ---: | ---: | ---: | ---: | ---: | --- |
| 2026-01-01T00:00:00Z | 3.304910″ | 0.033906″ | 0.033906″ | 3.271003″ | 3.271003″ | 酉 / 酉 |
| 2026-03-20T12:00:00Z | 1.060573″ | 0.114697″ | 0.114697″ | 0.945875″ | 0.945875″ | 戌 / 戌 |
| 2026-06-21T00:00:00Z | 0.749525″ | 0.081737″ | 0.081737″ | 0.831262″ | 0.831262″ | 巳 / 巳 |
| 2026-09-12T17:10:00Z | 1.396174″ | 0.073985″ | 0.073985″ | 1.470159″ | 1.470159″ | 巳 / 巳 |
| 2026-12-21T12:00:00Z | 3.086710″ | 0.134210″ | 0.134210″ | 2.952500″ | 2.952500″ | 酉 / 酉 |

### 交宫时间与边界风险

独立边界路径使用 Horizons 每两小时的位置和速度状态，以分段三次 Hermite 插值求任意中间时刻的 ICRF 月球向量，再独立执行 ERFA 转换和二分搜索。两小时网格及原始状态均保存在研究 fixture 中。

| 宫界 | Astronomy Engine UTC | JPL + ERFA UTC | AE − 独立实现 |
| --- | --- | --- | ---: |
| 巳→辰 | 2026-09-13T06:33:09.087Z | 2026-09-13T06:33:12.041Z | -2.954 s |
| 辰→卯 | 2026-09-15T17:26:55.524Z | 2026-09-15T17:26:59.652Z | -4.128 s |
| 卯→寅 | 2026-09-18T00:33:03.394Z | 2026-09-18T00:33:05.991Z | -2.597 s |
| 寅→丑 | 2026-09-20T06:29:51.380Z | 2026-09-20T06:29:56.511Z | -5.131 s |

最大交宫时间差为 `5.131 s`。针对每个宫界，另取前后距离宫界 `<10′`、`<1′`、`<0.1′` 的共 24 个风险点；两套算法的宫位和前后切换方向全部一致。

### 偏差与结论

没有发现会造成整体宫位偏移的系统性角偏差。月球方向差稳定在约 `1.09″–2.96″`；β Aqr/子宫中心的两种日期坐标转换最大 RA 差约 `0.14″`。Astronomy Engine 的地月距离相对 DE441 有约 `-10.76 km` 的平均径向偏差，但十二宫只使用方向，因而不影响本研究结论。

20 个全年样本、原有 5 个 fixture 时刻和 24 个宫界风险点全部得到相同 `moonPalace`；四次交宫最大时间差 `5.131 s < 60 s`，满足当前六壬分钟级排盘精度目标，可以进入生产接入阶段。

复现命令：

```bash
python -m pip install -r tools/requirements-moon-palace-crosscheck.txt
python tools/moon_palace_crosscheck.py
python -m unittest tests/Tools/moon_palace_crosscheck_test.py
```

只有需要从官方服务刷新原始 Horizons 数据时才运行：

```bash
python tools/moon_palace_crosscheck.py --fetch
```
