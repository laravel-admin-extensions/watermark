<?php

namespace Jxlwqq\Watermark;

use Encore\Admin\Facades\Admin;
use Illuminate\Support\ServiceProvider;

class WatermarkServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot(Watermark $extension)
    {
        if (!Watermark::boot()) {
            return;
        }

        Admin::booting(function () {
            $user = Admin::user();
            if (empty($user)) {
                return;
            }
            $content = config('admin.extensions.watermark.config.content');
            $content = Admin::user()->$content ?? $content;
            $width = config('admin.extensions.watermark.config.width') ?? '100px';
            $height = config('admin.extensions.watermark.config.height') ?? '120px';
            $textAlign = config('admin.extensions.watermark.config.textAlign') ?? 'left';
            $textBaseline = config('admin.extensions.watermark.config.textBaseline') ?? 'alphabetic';
            $font = config('admin.extensions.watermark.config.font') ?? '15px Times New Roman';
            $fillStyle = config('admin.extensions.watermark.config.fillStyle') ?? 'rgba(204,204,204,0.4)';
            $rotate = config('admin.extensions.watermark.config.rotate') ?? 30;
            $zIndex = config('admin.extensions.watermark.config.zIndex') ?? 1000;

            Admin::script($this->script(
                $content,
                $width,
                $height,
                $textAlign,
                $textBaseline,
                $font,
                $fillStyle,
                $rotate,
                $zIndex
            ));
        });
    }

    protected function script($content, $width, $height, $textAlign, $textBaseline, $font, $fillStyle, $rotate, $zIndex)
    {
        return <<<JS
;(function () {
      function __canvasWM({
        container = document.body,
        width = '{$width}',
        height = '{$height}',
        textAlign = '{$textAlign}',
        textBaseline = '{$textBaseline}',
        font = '{$font}',
        fillStyle = '{$fillStyle}',
        content = '{$content}',
        rotate = {$rotate},
        zIndex = {$zIndex}
      } = {}) {
        const args = arguments[0];
        const canvas = document.createElement('canvas');
        canvas.setAttribute('width', width);
        canvas.setAttribute('height', height);
        const ctx = canvas.getContext("2d");
        ctx.shadowOffsetX = 2;
        ctx.shadowOffsetY = 2;
        ctx.shadowBlur = 2;
        ctx.textAlign = textAlign;
        ctx.textBaseline = textBaseline;
        ctx.font = font;
        ctx.fillStyle = fillStyle;
        ctx.rotate(Math.PI / 180 * rotate);
        ctx.fillText(content, parseFloat(width) / 2, parseFloat(height) / 2);

        const base64Url = canvas.toDataURL();
        const __wm = document.querySelector('.__wm');

        const watermarkDiv = __wm || document.createElement("div");
        const styleStr = "position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;background-repeat:repeat;z-index:" + zIndex + ";background-image:url('" +  base64Url +"')";
        watermarkDiv.setAttribute('style', styleStr);
        watermarkDiv.classList.add('__wm');

        if (!__wm) {
          container.style.position = 'relative';
          container.insertBefore(watermarkDiv, container.firstChild);
        }

        const MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
        if (MutationObserver) {
          let mo = new MutationObserver(function () {
            const __wm = document.querySelector('.__wm');
            if ((__wm && __wm.getAttribute('style') !== styleStr) || !__wm) {
              mo.disconnect();
              mo = null;
            __canvasWM(JSON.parse(JSON.stringify(args)));
            }
          });

          mo.observe(container, {
            attributes: true,
            subtree: true,
            childList: true
          })
        }

      }

      if (typeof module != 'undefined' && module.exports) {
        module.exports = __canvasWM;
      } else if (typeof define == 'function' && define.amd) {
        define(function () {
          return __canvasWM;
        });
      } else {
        window.__canvasWM = __canvasWM;
      }
    })();

    __canvasWM({});
JS;
    }
}
