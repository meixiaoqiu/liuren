// 在页面加载完成后执行
(function() {
  'use strict';

  // 防止同一页面重复执行
  const EXECUTION_MARKER = '__autoClickExecuted__';
  if (window[EXECUTION_MARKER]) {
    return;
  }
  window[EXECUTION_MARKER] = true;

  // 查找 Create 按钮（使用 XPath 精准匹配）
  function findCreateButton() {
    const xpath = '//button[starts-with(@id, "key-bindings") and .//span[@class="fi-btn-label" and normalize-space(.) = "Create"]]';
    const result = document.evaluate(xpath, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null);
    return result.singleNodeValue;
  }

  // 点击按钮
  function clickButton(button) {
    if (button && !button.disabled && button.offsetParent !== null) {
      button.click();
      console.log('[Auto Click] Create 按钮已点击');
      return true;
    } else {
      console.log('[Auto Click] 按钮不可点击或已从页面移除');
      return false;
    }
  }

  // 生成随机延迟（10-30秒）
  function getRandomDelay() {
    return Math.floor(Math.random() * 21000) + 10000; // 10000-31000ms
  }

  // 尝试点击按钮的循环
  function startClickLoop() {
    const attemptClick = () => {
      const button = findCreateButton();
      if (button) {
        console.log('[Auto Click] 找到 Create 按钮，尝试点击...');
        clickButton(button);
      } else {
        console.log('[Auto Click] 未找到 Create 按钮');
      }

      // 设置下一次尝试（随机10-30秒后）
      const nextDelay = getRandomDelay();
      console.log(`[Auto Click] 下次尝试将在 ${(nextDelay / 1000).toFixed(1)} 秒后`);
      setTimeout(attemptClick, nextDelay);
    };

    // 立即执行第一次
    attemptClick();
  }

  // 初始化
  function init() {
    // 开始循环点击
    startClickLoop();
  }

  init();
})();
