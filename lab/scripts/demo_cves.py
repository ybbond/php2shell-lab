#!/usr/bin/env python3
"""Demo berdampingan CVE-2026-109618 & CVE-2026-109619 (unfixed vs fixed).

Dipakai saat presentasi ke dosen/kelas. Script menjalankan urutan demo yang
sama pada satu pasangan container (default: PHP 8.4 unfixed 8084 vs fixed 8184)
lalu menampilkan perbandingan hasilnya berdampingan.

Pemakaian:
    mise run lab:demo                      # pasangan PHP 8.4
    mise exec -- uv run python scripts/demo_cves.py --unfixed-port 8085 --fixed-port 8185
"""

from __future__ import annotations

import argparse

import httpx
from rich.console import Console
from rich.panel import Panel
from rich.table import Table

STEPS_618 = [
    "Picu OOM (zend_bailout) di tengah request",
    "Cek state internal yang tertinggal di worker yang sama",
]
STEPS_619 = [
    "Stage & free record (request A)",
    "Baca ulang pointer usang di worker yang sama (request B)",
]


def banner(c: Console, title: str) -> None:
    c.print()
    c.rule(f"[bold]{title}[/bold]")


def base_url(port: int) -> str:
    return f"http://localhost:{port}"


def worker_info(client: httpx.Client, port: int) -> dict:
    resp = client.get(f"{base_url(port)}/?format=json")
    resp.raise_for_status()
    return resp.json()


def get_endpoint(client: httpx.Client, port: int, path: str) -> httpx.Response:
    return client.get(f"{base_url(port)}/{path}")


def verdict_of(resp: httpx.Response) -> tuple[str, str]:
    """Ambil (verdict, detail) dari halaman status demo."""
    text = resp.text
    for needle in ("USE AFTER FREE", "VULNERABLE PATTERN", "SAFE (rolled back)", "SAFE", "CLEAN"):
        if f">{needle}</" in text:
            start = text.find(f">{needle}</")
            row_start = text.rfind("<tr>", 0, start)
            row_end = text.find("</tr>", start)
            detail = text[row_start:row_end]
            detail = detail[detail.find("</th>") + 5 :] if "</th>" in detail else ""
            return needle, " ".join(detail.split())[:180]
    return "?", ""


def worker_table(c: Console, client: httpx.Client, unfixed_port: int, fixed_port: int) -> None:
    table = Table(title="Langkah 0 — Determinisme worker (MaxRequestWorkers = 1)")
    table.add_column("Container")
    table.add_column("PHP")
    table.add_column("Worker PID (3 request)")
    table.add_column("memory_limit")
    for label, port, expect in (("unfixed", unfixed_port, "red"), ("fixed", fixed_port, "green")):
        info = worker_info(client, port)
        pids = [str(worker_info(client, port).get("worker_pid")) for _ in range(3)]
        same = len(set(pids)) == 1
        table.add_row(
            label,
            str(info.get("php_version")),
            f"{pids[0]} ({'[ok]' if same else '[BERUBAH!]'})",
            str(info.get("memory_limit")),
            style=expect,
        )
    c.print(table)


def demo_109618(c: Console, client: httpx.Client, unfixed_port: int, fixed_port: int) -> None:
    banner(c, "CVE-2026-109618 — OOM bailout meninggalkan state parsial tanpa rollback")
    rows = []
    for label, port in (("UNFIXED", unfixed_port), ("FIXED", fixed_port)):
        get_endpoint(client, port, "cve109618_oom.php?action=reset")
        oom = get_endpoint(client, port, "cve109618_oom.php?action=oom")
        fatal = "Allowed memory size" in oom.text
        status = get_endpoint(client, port, "cve109618_oom.php?action=status")
        verdict, detail = verdict_of(status)
        rows.append((label, port, fatal, verdict, detail))

    table = Table(title=" → ".join(STEPS_618))
    table.add_column("Versi")
    table.add_column("Port", justify="right")
    table.add_column("zend_bailout (Fatal: memory exhausted)", justify="center")
    table.add_column("State setelah OOM")
    table.add_column("Detail")
    for label, port, fatal, verdict, detail in rows:
        table.add_row(
            label,
            str(port),
            "[yellow]YES[/yellow]" if fatal else "[red]TIDAK terdeteksi[/red]",
            f"[red]{verdict}[/red]" if "VULNERABLE" in verdict else f"[green]{verdict}[/green]",
            detail,
        )
    c.print(table)
    c.print(
        Panel(
            "Poin demo: kedua versi mengalami OOM fatal yang SAMA. Bedanya hanya "
            "apa yang tertinggal setelahnya — unfixed menyisakan state 'in_flight' "
            "yang terinisialisasi sebagian (tidak di-rollback), fixed membersihkannya.",
            title="Narasi ke kelas",
            border_style="cyan",
        )
    )


def demo_109619(c: Console, client: httpx.Client, unfixed_port: int, fixed_port: int) -> None:
    banner(c, "CVE-2026-109619 — Use-after-free pada state worker (request A vs B, PID sama)")
    rows = []
    for label, port in (("UNFIXED", unfixed_port), ("FIXED", fixed_port)):
        get_endpoint(client, port, "cve109619_uaf.php?action=reset")
        get_endpoint(client, port, "cve109619_uaf.php?action=stage")  # request A
        read = get_endpoint(client, port, "cve109619_uaf.php?action=read")  # request B
        verdict, detail = verdict_of(read)
        rows.append((label, port, verdict, detail))

    table = Table(title=" → ".join(STEPS_619))
    table.add_column("Versi")
    table.add_column("Port", justify="right")
    table.add_column("Hasil request B")
    table.add_column("Detail")
    for label, port, verdict, detail in rows:
        table.add_row(
            label,
            str(port),
            f"[red]{verdict}[/red]" if verdict == "USE AFTER FREE" else f"[green]{verdict}[/green]",
            detail,
        )
    c.print(table)
    c.print(
        Panel(
            "Poin demo: request A dan B dilayani worker PID yang sama (cek Langkah 0). "
            "Pada unfixed, pointer di registry worker tidak dibersihkan saat record "
            "dibebaskan, sehingga request B membaca data usang. Pada fixed, pointer "
            "ikut dihapus — tidak ada referensi yang tersisa.",
            title="Narasi ke kelas",
            border_style="cyan",
        )
    )


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--unfixed-port", type=int, default=8084)
    parser.add_argument("--fixed-port", type=int, default=8184)
    args = parser.parse_args()

    c = Console()
    with httpx.Client(timeout=10.0) as client:
        worker_table(c, client, args.unfixed_port, args.fixed_port)
        demo_109618(c, client, args.unfixed_port, args.fixed_port)
        demo_109619(c, client, args.unfixed_port, args.fixed_port)

    c.print()
    c.print("[bold green]Demo selesai.[/bold green] Buka URL berikut di browser untuk tunjukkan detail state:")
    c.print(f"  unfixed: {base_url(args.unfixed_port)}/cve109618_oom.php  |  {base_url(args.unfixed_port)}/cve109619_uaf.php")
    c.print(f"  fixed  : {base_url(args.fixed_port)}/cve109618_oom.php  |  {base_url(args.fixed_port)}/cve109619_uaf.php")


if __name__ == "__main__":
    main()
