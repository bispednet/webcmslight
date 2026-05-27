#!/usr/bin/env bash
# Retry failed product image downloads with corrected CDN URLs
# Run: bash scripts/retry-product-images.sh
set -e
DEST="/home/funboy/bisped.net/public/media/products"
mkdir -p "$DEST"

UA="Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120 Safari/537.36"
MIN_SIZE=4000

dl() {
    local slug="$1"
    local url="$2"
    local ext="${3:-jpg}"
    local out="$DEST/${slug}.${ext}"

    # Check if a valid file already exists (any extension)
    for existing in "$DEST/${slug}.jpg" "$DEST/${slug}.png"; do
        if [ -f "$existing" ] && [ "$(stat -c%s "$existing" 2>/dev/null || echo 0)" -gt "$MIN_SIZE" ]; then
            echo "  [skip] ${slug}"
            return 0
        fi
    done

    echo "  [get]  ${slug}"
    curl -sL --max-time 30 --user-agent "$UA" -o "$out" "$url" 2>/dev/null

    local size
    size=$(stat -c%s "$out" 2>/dev/null || echo 0)
    if [ "$size" -lt "$MIN_SIZE" ]; then
        echo "  [FAIL] ${slug} — ${size} bytes (too small)"
        rm -f "$out"
    else
        echo "  [ OK ] ${slug} — ${size} bytes"
    fi
}

echo "=== Retrying failed product images ==="

# ── Smartphones (GSMArena CDN) ────────────────────────────────────────────────
dl "samsung-galaxy-s25-256gb" \
   "https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-s25-sm-s931.jpg"

dl "samsung-galaxy-a55-5g-256gb" \
   "https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-a55.jpg"

dl "google-pixel-9-128gb" \
   "https://fdn2.gsmarena.com/vv/bigpic/google-pixel-9-.jpg"

dl "xiaomi-14t-pro-512gb" \
   "https://fdn2.gsmarena.com/vv/bigpic/xiaomi-14t-pro.jpg"

# ── Wearable / Tablet (GSMArena CDN) ─────────────────────────────────────────
dl "samsung-galaxy-watch-7-44mm" \
   "https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-watch7.jpg"

dl "samsung-galaxy-tab-s9-fe-128gb-wi-fi" \
   "https://fdn2.gsmarena.com/vv/bigpic/samsung-galaxy-tab-s9-fe-10-.jpg"

# ── Notebooks ────────────────────────────────────────────────────────────────
dl "asus-tuf-gaming-a15-rtx-4060-ryzen-7" \
   "https://www.notebookcheck.net/uploads/tx_nbc2/Asus_TUF_Gaming_A15_FA507UI.jpg"

dl "asus-vivobook-16x-oled-i5-16gb-512gb" \
   "https://dlcdnwebimgs.asus.com/gain/a9fcc542-54ae-4187-bfa2-5c5cbcfab375//w800" \
   "jpg"

dl "lenovo-loq-15-rtx-4060-i5-12450hx" \
   "https://www.notebookcheck.net/fileadmin/_processed_/1/5/csm_4zu3_Lenovo_LOQ_15IRX9_35468c6102.jpg"

dl "lenovo-ideapad-5-15-ryzen-5-16gb-512gb" \
   "https://www.notebookcheck.net/fileadmin/_processed_/d/b/csm_Lenovo_IdeaPad_5_15ALC05_Test_Teaser_3_7076f8e841.jpg"

# ── Gaming peripherals ────────────────────────────────────────────────────────
dl "logitech-g-pro-x-superlight-2" \
   "https://resource.logitechg.com/w_800,c_lpad,ar_4:3,q_auto,f_jpg,dpr_1.0/d_transparent.gif/content/dam/gaming/en/products/pro-x-superlight-2/new-gallery-assets-2025/pro-x-superlight-2-mice-top-angle-white-gallery-1.png"

dl "corsair-k70-rgb-mk-2-cherry-mx-red" \
   "https://assets.corsair.com/image/upload/c_pad,q_85,h_800,w_800,f_jpg/products/Gaming-Keyboards/CH-9109012-NA/Gallery/K70_RGB_MK2_01.jpg"

dl "samsung-odyssey-g5-27-qhd-165hz" \
   "https://www.notebookcheck.net/fileadmin/_processed_/f/7/csm_Samsung_S32AG5259_3e264c8ebb.jpg"

# ── Audio ─────────────────────────────────────────────────────────────────────
dl "sony-wh-1000xm5" \
   "https://www.soundguys.com/wp-content/uploads/2023/10/Sony-WXM5-Headphones-Featured-Image-1-scaled.jpg"

dl "hyperx-cloud-alpha-wireless" \
   "https://hyperx.com/cdn/shop/files/hyperx_cloud_alpha_wireless_1_main_99b8c28d-57bd-42df-bc89-b1d776d5d1d5.jpg?v=1763563176"

# ── Connectivity ──────────────────────────────────────────────────────────────
dl "fritz-box-7590-ax-wi-fi-6" \
   "https://fritz.com/cdn/shop/files/fritzbox_7590_ax_dsl_2000x2000px.webp?v=1773999819"

dl "tp-link-archer-axe75-wi-fi-6e-tri-band" \
   "https://static.tp-link.com/upload/image-line/Archer_AXE75_EU_2.0_Overview_01_normal_20260304073407i.jpg"

# ── Mini PC ───────────────────────────────────────────────────────────────────
dl "mini-pc-intel-n100-16gb-512gb-ssd" \
   "https://www.gmktec.com/cdn/shop/files/35acf86751d1ba7ac95843daebc961a7.webp?v=1770966674&width=800"

echo ""
echo "=== Done. Final file sizes ==="
for f in "$DEST"/*.jpg "$DEST"/*.png; do
    [ -f "$f" ] || continue
    size=$(stat -c%s "$f" 2>/dev/null || echo 0)
    [ "$size" -gt "$MIN_SIZE" ] && echo "  ✓ $(basename "$f"): ${size} bytes" || echo "  ✗ $(basename "$f"): ${size} bytes (MISSING/TOO SMALL)"
done
