#!/usr/bin/env python3
"""Script pemeriksa kesiapan semua container PHP (unfixed & fixed) di lab."""

from __future__ import annotations

import httpx
from rich.console import Console
from rich.table import Table

TARGETS = [
    ("php85-unfixed", "8.5 (Unfixed)", "http://localhost:8085/?format=json"),
    ("php84-unfixed", "8.4 (Unfixed)", "http://localhost:8084/?format=json"),
    ("php83-unfixed", "8.3 (Unfixed)", "http://localhost:8083/?format=json"),
    ("php82-unfixed", "8.2 (Unfixed)", "http://localhost:8082/?format=json"),
    ("php85-fixed", "8.5 (Fixed)", "http://localhost:8185/?format=json"),
    ("php84-fixed", "8.4 (Fixed)", "http://localhost:8184/?format=json"),
    ("php83-fixed", "8.3 (Fixed)", "http://localhost:8183/?format=json"),
    ("php82-fixed", "8.2 (Fixed)", "http://localhost:8182/?format=json"),
]


def main() -> None:
    console = Console()
    table = Table(title="Status Lab Container PHP (Unfixed vs Fixed)")
    table.add_column("Container", style="cyan", no_wrap=True)
    table.add_column("Target Branch", style="white")
    table.add_column("URL", style="dim")
    table.add_column("HTTP", justify="center")
    table.add_column("PHP Version", style="bold")
    table.add_column("Patch Status", justify="center")
    table.add_column("Worker PID", justify="right")

    with httpx.Client(timeout=3.0) as client:
        for name, branch, url in TARGETS:
            try:
                resp = client.get(url)
                data = resp.json()
                status_label = data.get("lab_status", "unknown")
                badge = (
                    "[bold red]UNFIXED[/bold red]"
                    if status_label == "unfixed"
                    else "[bold green]FIXED[/bold green]"
                )
                table.add_row(
                    name,
                    branch,
                    url.replace("/?format=json", ""),
                    f"[green]{resp.status_code}[/green]",
                    str(data.get("php_version", "-")),
                    badge,
                    str(data.get("worker_pid", "-")),
                )
            except Exception as exc:
                table.add_row(
                    name,
                    branch,
                    url.replace("/?format=json", ""),
                    "[red]DOWN[/red]",
                    "-",
                    "-",
                    f"[dim]{type(exc).__name__}[/dim]",
                )

    console.print(table)


if __name__ == "__main__":
    main()
