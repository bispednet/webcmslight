#!/usr/bin/env bash
# Download official product images for bisped.net catalog
# Run: bash scripts/download-product-images.sh
set -e
DEST="/home/funboy/bisped.net/public/media/products"
mkdir -p "$DEST"

UA="Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120 Safari/537.36"

dl() {
    local slug="$1"
    local url="$2"
    local out="$DEST/${slug}.jpg"
    if [ -f "$out" ] && [ -s "$out" ]; then
        echo "  [skip] ${slug}"
        return 0
    fi
    echo "  [get]  ${slug}"
    curl -sL --max-time 20 --user-agent "$UA" -o "$out" "$url" 2>/dev/null
    if [ ! -s "$out" ]; then
        echo "  [FAIL] ${slug} — removing empty file"
        rm -f "$out"
    fi
}

echo "=== Downloading product images ==="

# ── Smartphones ─────────────────────────────────────────────────────────────
dl "samsung-galaxy-s25-256gb" \
   "https://images.samsung.com/is/image/samsung/p6pim/levant/2501/gallery/levant-galaxy-s25-sm-s931-sm-s931bzageub-543218614?$650_519_PNG$"

dl "apple-iphone-16-128gb" \
   "https://store.storeimages.cdn-apple.com/8756/as-images.apple.com/is/iphone16-digitalmat-gallery-1-202409?wid=800&hei=800&fmt=jpeg&qlt=90"

dl "xiaomi-14t-pro-512gb" \
   "https://i01.appmifile.com/mi_next/01/2024/05/20/c9c77e4d6ac4dbc2e3b60b5b7c22d6ea.png"

dl "samsung-galaxy-a55-5g-256gb" \
   "https://images.samsung.com/is/image/samsung/p6pim/levant/2403/gallery/levant-galaxy-a55-5g-sm-a556-sm-a556ezkaeub-thumb-541239624?$650_519_PNG$"

dl "google-pixel-9-128gb" \
   "https://lh3.googleusercontent.com/yzWR3vv2nD3dYpzipqxHqvVmkJJO-MqSBqHXI7EVrEFxZ48TnPqyVb3yVVJcfNSmxGi2hM6GDQR1cg=w800-h800"

# ── Notebooks & PC ──────────────────────────────────────────────────────────
dl "apple-macbook-air-13-m3-8gb-256gb" \
   "https://store.storeimages.cdn-apple.com/8756/as-images.apple.com/is/mba13-midnight-select-202402?wid=904&hei=840&fmt=jpeg&qlt=90"

dl "asus-vivobook-16x-oled-i5-16gb-512gb" \
   "https://dlcdnwebimgs.asus.com/gain/4d1c21f5-fc87-41f2-b491-cd26cb5f27fc/w800/h533"

dl "lenovo-ideapad-5-15-ryzen-5-16gb-512gb" \
   "https://p3-ofp.static.pub/fes/cms/2022/09/06/y6cgnrp4ohogzdmjfnhtbcjmjgd7w9537754.png"

# ── Gaming ──────────────────────────────────────────────────────────────────
dl "asus-tuf-gaming-a15-rtx-4060-ryzen-7" \
   "https://dlcdnwebimgs.asus.com/gain/8f5fb3ec-8a53-4d73-b30c-fe5dbc4124c6/w800/h533"

dl "lenovo-loq-15-rtx-4060-i5-12450hx" \
   "https://p3-ofp.static.pub/fes/cms/2023/08/01/3w6g5ld1c7uzstjlx39j5gp1dq7il8843001.png"

dl "samsung-odyssey-g5-27-qhd-165hz" \
   "https://images.samsung.com/is/image/samsung/p6pim/levant/lc27g55tqwuxen/gallery/levant-odyssey-g5-c27g55tqwu-lc27g55tqwuxen-531842703?$650_519_PNG$"

dl "logitech-g-pro-x-superlight-2" \
   "https://resource.logitech.com/content/dam/gaming/en/products/pro-x-superlight-2/pro-x-superlight-2-gallery-1.png"

dl "hyperx-cloud-alpha-wireless" \
   "https://media.kingston.com/hyperx/feature/hhsc1a-dblk-g-img.jpg"

dl "corsair-k70-rgb-mk-2-cherry-mx-red" \
   "https://www.corsair.com/medias/sys_master/images/images/hb3/hb6/9315534430238/-CH-9109012-NA-Gallery-K70-RGB-MK-2-01.png"

# ── Audio ────────────────────────────────────────────────────────────────────
dl "sony-wh-1000xm5" \
   "https://www.sony.it/image/7bcab38f9b74f798e31d5d69eb8b34b7?fmt=pjpeg&bgcolor=FFFFFF&bgc=FFFFFF&wid=800&hei=800"

dl "apple-airpods-pro-2a-gen-usb-c" \
   "https://store.storeimages.cdn-apple.com/8756/as-images.apple.com/is/MQD83?wid=800&hei=800&fmt=jpeg&qlt=90"

# ── Wearable / Tablet ────────────────────────────────────────────────────────
dl "samsung-galaxy-watch-7-44mm" \
   "https://images.samsung.com/is/image/samsung/p6pim/levant/2406/gallery/levant-galaxy-watch7-sm-l310-sm-l310nzsaxfe-thumb-541596756?$650_519_PNG$"

dl "samsung-galaxy-tab-s9-fe-128gb-wi-fi" \
   "https://images.samsung.com/is/image/samsung/p6pim/levant/2309/gallery/levant-galaxy-tab-s9-fe-128gb-sm-x510-sm-x510nzaaxfe-thumb-537619000?$650_519_PNG$"

# ── Connettività ─────────────────────────────────────────────────────────────
dl "tp-link-archer-axe75-wi-fi-6e-tri-band" \
   "https://static.tp-link.com/2022/202202/20220210/Archer%20AXE75_EU_1.0_001.jpg"

dl "fritz-box-7590-ax-wi-fi-6" \
   "https://assets.avm.de/files/docs/fritzbox/fritzbox-7590-ax/fritzbox-7590-ax_prod_en_web.jpg"

# ── Mini PC ──────────────────────────────────────────────────────────────────
dl "mini-pc-intel-n100-16gb-512gb-ssd" \
   "https://m.media-amazon.com/images/I/71GpLFZrTLL._AC_SL1500_.jpg"

echo ""
echo "=== Done. Checking files ==="
for f in "$DEST"/*.jpg; do
    size=$(stat -c%s "$f" 2>/dev/null || echo 0)
    echo "  $(basename $f): ${size} bytes"
done
