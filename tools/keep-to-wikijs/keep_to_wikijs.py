#!/usr/bin/env python3
"""แปลงโน้ต Google Keep (จาก Google Takeout) เป็นไฟล์ Markdown ที่ Wiki.js import ได้

ใช้ stdlib ล้วน — ไม่ต้องลง dependency ใด ๆ (ต้องการ Python 3.9+)

flow การใช้งาน:
  1. Export โน้ตผ่าน Google Takeout → ได้โฟลเดอร์ Takeout/Keep/ ที่มีไฟล์ .json ต่อ 1 โน้ต
  2. รันสคริปต์นี้ชี้ไปที่โฟลเดอร์นั้น → ได้ไฟล์ .md พร้อม front-matter ของ Wiki.js
     จัดโฟลเดอร์ตาม label + แปลง label เป็น tag ให้อัตโนมัติ
  3. เอาโฟลเดอร์ผลลัพธ์ไปวางใน Git repo / Local FS ที่ผูกกับ Wiki.js แล้วกด
     "Import Everything" ใน Admin → Storage

ตัวอย่าง:
  python3 keep_to_wikijs.py --input ./Takeout/Keep --output ./wiki-content
  python3 keep_to_wikijs.py -i ./Takeout/Keep -o ./out --include-trashed --dry-run
"""

from __future__ import annotations

import argparse
import json
import re
import shutil
import sys
import unicodedata
from datetime import datetime, timezone
from pathlib import Path

# ---------------------------------------------------------------------------
# helpers
# ---------------------------------------------------------------------------

_WS = re.compile(r"\s+")
_DASHES = re.compile(r"-{2,}")


def slugify(text: str, maxlen: int = 80) -> str:
    """สร้าง slug ที่ปลอดภัยสำหรับ path/URL โดย *คงตัวอักษรไทยไว้ครบ*

    Wiki.js รับ path เป็น unicode ได้ จึงไม่แปลงไทยเป็น ascii ทิ้ง (ไม่งั้นชื่อไทยจะหายหมด)
    เก็บอักขระ category L (ตัวอักษร), M (สระ/วรรณยุกต์ไทยเป็น combining mark — \\w ไม่นับ!),
    N (ตัวเลข) และ - _ ที่เหลือตัดทิ้ง
    """
    text = _WS.sub("-", text.strip().lower())
    kept = [
        ch for ch in text
        if ch in "-_" or unicodedata.category(ch)[0] in ("L", "M", "N")
    ]
    text = _DASHES.sub("-", "".join(kept)).strip("-_")
    return text[:maxlen].strip("-_")


def to_iso(usec: int | None) -> str | None:
    """แปลง timestamp หน่วยไมโครวินาที (Keep เก็บเป็น usec) → ISO8601 แบบที่ Wiki.js เขียน"""
    if not usec:
        return None
    try:
        dt = datetime.fromtimestamp(int(usec) / 1_000_000, tz=timezone.utc)
    except (ValueError, OverflowError, OSError):
        return None
    return dt.strftime("%Y-%m-%dT%H:%M:%S.000Z")


def yaml_dq(text: str) -> str:
    """คืน string แบบ double-quoted ที่ปลอดภัยสำหรับ YAML front-matter"""
    text = text.replace("\\", "\\\\").replace('"', '\\"')
    text = text.replace("\n", " ").replace("\r", "")
    return f'"{text}"'


# ---------------------------------------------------------------------------
# แปลงเนื้อหาโน้ต
# ---------------------------------------------------------------------------

def pick_title(note: dict) -> str:
    """เลือกชื่อหน้า: title → บรรทัดแรกของเนื้อหา → รายการแรกใน checklist → ตามวันที่สร้าง"""
    title = (note.get("title") or "").strip()
    if title:
        return title

    text = (note.get("textContent") or "").strip()
    if text:
        return text.splitlines()[0].strip()[:80]

    for item in note.get("listContent") or []:
        item_text = (item.get("text") or "").strip()
        if item_text:
            return item_text[:80]

    created = to_iso(note.get("createdTimestampUsec"))
    if created:
        return f"note-{created[:10]}"
    return "note"


def collect_tags(note: dict, *, status_tags: bool, color_tags: bool) -> list[str]:
    tags: list[str] = []
    for label in note.get("labels") or []:
        name = (label.get("name") or "").strip()
        if name and name not in tags:
            tags.append(name)

    if status_tags:
        if note.get("isPinned"):
            tags.append("pinned")
        if note.get("isArchived"):
            tags.append("archived")

    if color_tags:
        color = (note.get("color") or "").strip().upper()
        if color and color != "DEFAULT":
            tags.append(f"color-{color.lower()}")

    return tags


def note_folder(note: dict) -> str:
    """โฟลเดอร์ = label แรก (slug) ไม่มี label → uncategorized"""
    for label in note.get("labels") or []:
        name = (label.get("name") or "").strip()
        if name:
            return slugify(name) or "uncategorized"
    return "uncategorized"


def render_body(note: dict, asset_links: list[tuple[str, str]]) -> str:
    parts: list[str] = []

    text = (note.get("textContent") or "").replace("\r\n", "\n").rstrip()
    if text:
        parts.append(text)

    list_content = note.get("listContent") or []
    if list_content:
        lines = []
        for item in list_content:
            box = "[x]" if item.get("isChecked") else "[ ]"
            lines.append(f"- {box} {(item.get('text') or '').strip()}")
        parts.append("\n".join(lines))

    # รูป/ไฟล์แนบ — link แบบ relative (ไฟล์ถูกก๊อปวางข้างโน้ต)
    if asset_links:
        img_lines = [f"![{alt}]({href})" for alt, href in asset_links]
        parts.append("\n\n".join(img_lines))

    # weblink annotations → ต่อท้ายเป็นหัวข้อ "ลิงก์ที่แนบ"
    links = []
    for ann in note.get("annotations") or []:
        if ann.get("source") == "WEBLINK" and ann.get("url"):
            label = (ann.get("title") or ann.get("url")).strip()
            links.append(f"- [{label}]({ann['url']})")
    if links:
        parts.append("## ลิงก์ที่แนบ\n" + "\n".join(links))

    return "\n\n".join(parts).strip() + "\n"


def build_frontmatter(note: dict, title: str, tags: list[str]) -> str:
    created = to_iso(note.get("createdTimestampUsec"))
    edited = to_iso(note.get("userEditedTimestampUsec"))
    # เผื่อฟิลด์ขาด: ใช้ค่าที่มีแทนกัน สุดท้ายค่อย fallback เป็นเวลาปัจจุบัน
    now = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%S.000Z")
    edited = edited or created or now
    created = created or edited

    lines = [
        "---",
        f"title: {yaml_dq(title)}",
        'description: ""',
        "published: true",
        f"date: {edited}",
    ]
    if tags:
        lines.append("tags:")
        lines.extend(f"  - {yaml_dq(t)}" for t in tags)
    else:
        lines.append("tags: []")
    lines.append("editor: markdown")
    lines.append(f"dateCreated: {created}")
    lines.append("---")
    return "\n".join(lines)


# ---------------------------------------------------------------------------
# main
# ---------------------------------------------------------------------------

def unique_path(rel: Path, used: set[str]) -> Path:
    """กันชื่อชนกัน — ต่อ -2, -3 ... ถ้าซ้ำ"""
    key = rel.as_posix().lower()
    if key not in used:
        used.add(key)
        return rel
    stem, suffix, parent = rel.stem, rel.suffix, rel.parent
    n = 2
    while True:
        candidate = parent / f"{stem}-{n}{suffix}"
        key = candidate.as_posix().lower()
        if key not in used:
            used.add(key)
            return candidate
        n += 1


def process(args: argparse.Namespace) -> int:
    in_dir = Path(args.input)
    out_dir = Path(args.output)
    if not in_dir.is_dir():
        print(f"[error] ไม่พบโฟลเดอร์ input: {in_dir}", file=sys.stderr)
        return 2

    json_files = sorted(in_dir.glob("*.json"))
    if not json_files:
        print(f"[error] ไม่พบไฟล์ .json ใน {in_dir} (ชี้ไปที่โฟลเดอร์ Takeout/Keep หรือยัง?)",
              file=sys.stderr)
        return 2

    used_paths: set[str] = set()
    stats = {"converted": 0, "trashed": 0, "archived": 0, "empty": 0,
             "assets": 0, "assets_missing": 0, "bad_json": 0}

    for jf in json_files:
        try:
            note = json.loads(jf.read_text(encoding="utf-8"))
        except (json.JSONDecodeError, UnicodeDecodeError):
            stats["bad_json"] += 1
            continue
        if not isinstance(note, dict):
            stats["bad_json"] += 1
            continue

        if note.get("isTrashed") and not args.include_trashed:
            stats["trashed"] += 1
            continue
        if note.get("isArchived"):
            stats["archived"] += 1
            if args.skip_archived:
                continue

        title = pick_title(note)
        folder = note_folder(note)
        slug = slugify(title) or f"note-{stats['converted'] + 1}"

        rel_md = unique_path(Path(folder) / f"{slug}.md", used_paths)
        dest_md = out_dir / rel_md

        # จัดการไฟล์แนบ (รูป) — ก๊อปวางข้างโน้ต แล้ว link แบบ relative
        # (dry-run: คำนวณลิงก์ให้ครบแต่ไม่ก๊อปไฟล์จริง)
        asset_links: list[tuple[str, str]] = []
        if args.copy_assets:
            for idx, att in enumerate(note.get("attachments") or [], start=1):
                fp = (att.get("filePath") or "").strip()
                if not fp:
                    continue
                src = in_dir / fp
                if not src.is_file():
                    stats["assets_missing"] += 1
                    continue
                asset_name = f"{rel_md.stem}-{idx}{Path(fp).suffix}"
                asset_links.append((title, asset_name))
                stats["assets"] += 1
                if not args.dry_run:
                    dest_md.parent.mkdir(parents=True, exist_ok=True)
                    shutil.copy2(src, dest_md.parent / asset_name)

        tags = collect_tags(note, status_tags=args.status_tags, color_tags=args.color_tags)
        body = render_body(note, asset_links)

        if not body.strip() and not asset_links:
            stats["empty"] += 1
            # ยังเขียนหน้าเปล่าให้ (มี title/tags) — ไม่ทิ้งข้อมูล meta

        content = build_frontmatter(note, title, tags) + "\n\n" + body

        if args.dry_run:
            print(f"[dry-run] {rel_md.as_posix()}  (tags: {', '.join(tags) or '-'})")
        else:
            dest_md.parent.mkdir(parents=True, exist_ok=True)
            dest_md.write_text(content, encoding="utf-8")
        stats["converted"] += 1

    # สรุปผล
    print("\n=== สรุป ===")
    print(f"  แปลงสำเร็จ : {stats['converted']} โน้ต"
          + (" (dry-run ไม่ได้เขียนไฟล์)" if args.dry_run else f" → {out_dir}"))
    print(f"  archived   : {stats['archived']}"
          + (" (ข้าม)" if args.skip_archived else " (รวมไว้ + tag 'archived')"))
    print(f"  trashed    : {stats['trashed']}"
          + (" (รวมไว้)" if args.include_trashed else " (ข้าม)"))
    if stats["empty"]:
        print(f"  หน้าว่าง   : {stats['empty']} (เขียนให้แต่ไม่มีเนื้อหา เหลือแค่ title/tags)")
    if args.copy_assets:
        print(f"  ไฟล์แนบ    : ก๊อป {stats['assets']} ไฟล์"
              + (f", หาไม่เจอ {stats['assets_missing']}" if stats["assets_missing"] else ""))
    if stats["bad_json"]:
        print(f"  ข้าม json เสีย: {stats['bad_json']}")
    return 0


def build_parser() -> argparse.ArgumentParser:
    p = argparse.ArgumentParser(
        description="แปลงโน้ต Google Keep (Takeout) → Markdown สำหรับ Wiki.js",
        formatter_class=argparse.ArgumentDefaultsHelpFormatter,
    )
    p.add_argument("-i", "--input", required=True,
                   help="โฟลเดอร์ Takeout/Keep ที่มีไฟล์ .json")
    p.add_argument("-o", "--output", required=True,
                   help="โฟลเดอร์ปลายทางสำหรับไฟล์ .md")
    p.add_argument("--include-trashed", action="store_true",
                   help="รวมโน้ตที่อยู่ในถังขยะด้วย (ปกติข้าม)")
    p.add_argument("--skip-archived", action="store_true",
                   help="ข้ามโน้ตที่ archive (ปกติรวมไว้ + ติด tag 'archived')")
    p.add_argument("--copy-assets", action=argparse.BooleanOptionalAction, default=True,
                   help="ก๊อปไฟล์แนบ (รูป) วางข้างโน้ต + ใส่ลิงก์ให้")
    p.add_argument("--status-tags", action=argparse.BooleanOptionalAction, default=True,
                   help="เพิ่ม tag 'pinned' / 'archived' ตามสถานะโน้ต")
    p.add_argument("--color-tags", action="store_true",
                   help="เพิ่ม tag ตามสีของโน้ต (เช่น color-red)")
    p.add_argument("--dry-run", action="store_true",
                   help="ทดลองรัน แสดงผลลัพธ์แต่ไม่เขียนไฟล์จริง")
    return p


def main() -> int:
    return process(build_parser().parse_args())


if __name__ == "__main__":
    raise SystemExit(main())
