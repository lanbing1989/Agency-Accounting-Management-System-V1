<?php
require 'db.php';
// === 用uuid参数，不用id ===
$uuid = $_GET['uuid'] ?? '';
if(!$uuid) die('参数错误');
// === 用uuid查询合同 ===
$stmt = $db->prepare("
SELECT a.*, t.content AS template_content, c.client_name, c.contact_person, c.contact_phone, c.contact_email, c.remark, a.seal_id, a.content_snapshot, a.sign_image
FROM contracts_agreement a
LEFT JOIN contract_templates t ON a.template_id = t.id
LEFT JOIN contracts c ON a.client_id = c.id
WHERE a.uuid = :uuid
");
$stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
$agreement = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$agreement) die('合同不存在');

// 服务期
$period = null;
if ($agreement['service_period_id']) {
    $period = $db->query("SELECT * FROM service_periods WHERE id={$agreement['service_period_id']}")->fetchArray(SQLITE3_ASSOC);
}
// 分段
$segment = null;
if ($agreement['service_segment_id']) {
    $segment = $db->query("SELECT * FROM service_segments WHERE id={$agreement['service_segment_id']}")->fetchArray(SQLITE3_ASSOC);
}

// 获取盖章图片
$seal_img = '';
if ($agreement['seal_id']) {
    $seal = $db->query("SELECT image_path FROM seal_templates WHERE id={$agreement['seal_id']}")->fetchArray(SQLITE3_ASSOC);
    if ($seal && file_exists($seal['image_path'])) $seal_img = $seal['image_path'];
}

// 签名图片
$signature_img = $agreement['sign_image'] ?? '';

// 处理签署日期
if (!empty($agreement['sign_date'])) {
    $sign_date = $agreement['sign_date'];
    $sign_year = date('Y', strtotime($sign_date));
    $sign_month = date('m', strtotime($sign_date));
    $sign_day = date('d', strtotime($sign_date));
} else {
    $sign_date = date('Y-m-d');
    $sign_year = date('Y');
    $sign_month = date('m');
    $sign_day = date('d');
}

$vars = [
    'client_name'    => $agreement['client_name'] ?? '',
    'contact_person' => $agreement['contact_person'] ?? '',
    'contact_phone'  => $agreement['contact_phone'] ?? '',
    'contact_email'  => $agreement['contact_email'] ?? '',
    'remark'         => $agreement['remark'] ?? '',
    'service_start'  => $period['service_start'] ?? '',
    'service_end'    => $period['service_end'] ?? '',
    'month_count'    => $period['month_count'] ?? '',
    'package_type'   => $period['package_type'] ?? '',
    // 价格优先用分段，否则用服务期
    'price_per_year' => $segment ? ($segment['price_per_year'] ?? '') : ($period['price_per_year'] ?? ''),
    'segment_fee'    => $segment['segment_fee'] ?? '',
    // 签署日期相关变量
    'sign_date'      => $sign_date,
    'sign_year'      => $sign_year,
    'sign_month'     => $sign_month,
    'sign_day'       => $sign_day,
];

// 渲染函数
function render_contract_template($tpl, $vars, $seal_img = '', $signature_img = '') {
    if ($seal_img && strpos($tpl, '{seal}') !== false) {
        $tpl = str_replace('{seal}', '<img src="' . $seal_img . '" style="height:60px;">', $tpl);
    }
    if (strpos($tpl, '{signature}') !== false) {
        if ($signature_img) {
            $tpl = str_replace('{signature}', '<img src="' . $signature_img . '" style="height:60px;">', $tpl);
        } else {
            $tpl = str_replace('{signature}', '<button id="showSignPad" class="btn btn-outline-primary btn-sm w-100 mt-3 mb-2">甲方在线签字</button><div id="signPadArea" style="display:none;margin-top:10px;"></div>', $tpl);
        }
    }
    foreach ($vars as $k => $v) $tpl = str_replace('{'.$k.'}', htmlspecialchars($v), $tpl);
    return $tpl;
}

// ========== 关键：未签署时动态渲染，已签署时用快照 ==========
if (empty($agreement['sign_image'])) {
    // 未签署，允许签字
    $content = render_contract_template($agreement['template_content'], $vars, $seal_img, '');
} else {
    // 已签署，优先用快照（含签字图片）
    if (!empty($agreement['content_snapshot'])) {
        $content = $agreement['content_snapshot'];
    } else {
        $content = render_contract_template($agreement['template_content'], $vars, $seal_img, $signature_img);
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>合同在线签署</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link rel="stylesheet" href="/bootstrap/css/bootstrap.min.css">
    <style>
    #signature-pad {
        border:1px solid #aaa;
        border-radius:8px;
        background:#fff;
        width: 100%;
        max-width: 400px;
        height: 180px;
        touch-action: none;
        display: block;
        margin: 0 auto;
    }
    @media (max-width: 600px) {
      #signature-pad {
        width: 100% !important;
        max-width: 100vw !important;
        height: 180px !important;
      }
      .btn {
        font-size: 18px;
        padding: 10px 0;
        width: 100%;
        margin-bottom: 12px;
      }
      body {
        font-size: 16px;
      }
      .container {
        padding-left: 3px;
        padding-right: 3px;
      }
    }
    /* 打印优化：隐藏按钮/警告等不需要打印的内容 */
    @media print {
      .noprint, .btn, .alert {
        display: none !important;
      }
      body {
        background: #fff !important;
      }
      .container {
        box-shadow: none !important;
        border: none !important;
        margin: 0 !important;
        padding: 0 !important;
      }
    }
    </style>
    <!-- 引入html2canvas CDN -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-3 mb-3" style="max-width:800px;">
    <h4 class="mb-3 text-center">合同在线签署</h4>
    <div class="bg-white p-3 p-md-4 rounded shadow-sm mb-4" style="white-space:pre-line;" id="contractContent">
        <?= $content ?>
    </div>
    <?php if ($signature_img): ?>
        <div class="noprint text-end mb-3">
            <button class="btn btn-primary" id="printBtn">打印合同</button>
            <button class="btn btn-success" id="genJpgBtn">生成图片</button>
        </div>
        <div class="alert alert-success">合同已签署完成，您可随时查看。</div>
        <div class="alert alert-info">如需下载PDF，请联系您的服务顾问发送给您。</div>
    <?php endif;?>
</div>

<?php if (empty($signature_img)): ?>
<script src="/bootstrap/signature_pad.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let showBtn = document.getElementById('showSignPad');
    if (showBtn) {
        showBtn.onclick = function() {
            const area = document.getElementById('signPadArea');
            area.innerHTML = `
                <div class="mb-2">
                  <canvas id="signature-pad" width="400" height="180" style="width:100%;height:180px;touch-action:none;"></canvas>
                </div>
                <div class="mb-2 d-flex gap-2 flex-wrap">
                  <button id="clear-sign" class="btn btn-warning btn-lg flex-fill">清除</button>
                  <button id="save-sign" class="btn btn-success btn-lg flex-fill">确认签署</button>
                </div>
                <div class="text-muted text-center" style="font-size:15px;">请用手指或触控笔在上方区域签名</div>
            `;
            area.style.display = '';

            // 自动滚动到签名区
            area.scrollIntoView({behavior: 'smooth', block: 'center'});

            // 适配移动端canvas
            function resizeCanvas() {
                var canvas = document.getElementById('signature-pad');
                var ratio =  Math.max(window.devicePixelRatio || 1, 1);
                var width = area.offsetWidth > 0 ? area.offsetWidth : 350;
                canvas.width = width * ratio;
                canvas.height = 180 * ratio;
                canvas.style.width = width + 'px';
                canvas.style.height = '180px';
                var ctx = canvas.getContext('2d');
                ctx.setTransform(1, 0, 0, 1, 0, 0); // Reset transform
                ctx.scale(ratio, ratio);
            }
            setTimeout(resizeCanvas, 100);
            window.addEventListener('resize', resizeCanvas);

            let canvas = document.getElementById('signature-pad');
            let pad = new SignaturePad(canvas, {
                backgroundColor: '#fff',
                minWidth: 1.5,
                maxWidth: 3,
            });

            document.getElementById('clear-sign').onclick = function() { pad.clear(); }
            document.getElementById('save-sign').onclick = function() {
                if (pad.isEmpty()) { alert('请先签名'); return; }
                let data = pad.toDataURL('image/png');
                // ========== 用uuid参数 ==========
                fetch('ht_agreement_sign_save.php?uuid=<?= urlencode($agreement['uuid']) ?>',{
                    method:'POST',
                    headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({ signature: data })
                }).then(r=>r.json()).then(res=>{
                    if(res.ok){
                        location.reload();
                    }else{
                        alert(res.msg||'保存失败');
                    }
                });
            }
        }
    }
});
</script>
<?php endif;?>

<?php if ($signature_img): ?>
<script>
function isSpecialBrowser() {
    // 小米、QQ、UC、夸克、微信、支付宝等
    return /MiuiBrowser|QQBrowser|UCBrowser|Quark|MicroMessenger|AlipayClient/i.test(navigator.userAgent);
}
document.addEventListener('DOMContentLoaded', function() {
    var printBtn = document.getElementById('printBtn');
    if (printBtn) {
        printBtn.onclick = function() {
            if (isSpecialBrowser()) {
                alert('当前浏览器不支持打印功能，请点击右上角菜单用系统浏览器（如Chrome/Safari）打开本页再打印，或联系顾问获取PDF文件。');
            } else {
                window.print();
            }
        }
    }
    var genJpgBtn = document.getElementById('genJpgBtn');
    if (genJpgBtn) {
        genJpgBtn.onclick = function() {
            var node = document.getElementById('contractContent');
            html2canvas(node, {backgroundColor: '#fff', scale: 2}).then(function(canvas) {
                var imgData = canvas.toDataURL('image/jpeg', 0.95);
                var link = document.createElement('a');
                link.href = imgData;
                link.download = 'contract.jpg';
                link.click();
            });
        };
    }
});
</script>
<?php endif;?>
</body>
</html>