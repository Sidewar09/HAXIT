import time

from colorama import Fore, Style, init

from map_generation import generate_map
from algorithms import breadth_first_search, depth_first_search

CLEAR_SCREEN = "\033[2J"
CURSOR_HOME = "\033[H"

ALGORITHMS = {
    1: ("BFS", breadth_first_search),
    2: ("DFS", depth_first_search),
}

SYMBOL_STYLES = {
    "S": Fore.GREEN,
    "E": Fore.RED,
    "empty": Fore.WHITE,
    "wall": Fore.LIGHTBLACK_EX,
    "frontier": Fore.CYAN,
    "path": Fore.BLUE,
    "head": Fore.LIGHTYELLOW_EX,
    "explored": Fore.YELLOW,
}


def paint(symbol, style_name):
    return f"{SYMBOL_STYLES[style_name]}{symbol}{Style.RESET_ALL}"


def render_grid(grid, spawnpoint, endpoint, current=None, visited=(), pending=(), path=()):
    frontier_cells = set(pending)
    path_cells = set(path)
    visited_cells = set(visited)

    lines = []
    for row_index, row in enumerate(grid):
        painted_row = []
        for column_index, symbol in enumerate(row):
            cell = (row_index, column_index)

            if cell == spawnpoint:
                painted_row.append(paint("S", "S"))
            elif cell == endpoint:
                painted_row.append(paint("E", "E"))
            elif cell in path_cells:
                painted_row.append(paint("+", "path"))
            elif cell == current:
                painted_row.append(paint("@", "head"))
            elif cell in frontier_cells:
                painted_row.append(paint("*", "frontier"))
            elif cell in visited_cells:
                painted_row.append(paint(".", "explored"))
            elif symbol == "#":
                painted_row.append(paint("#", "wall"))
            else:
                painted_row.append(paint(".", "empty"))

        lines.append("  ".join(painted_row))

    return "\n".join(lines)


def draw_frame(text, status):
    print(CURSOR_HOME + text)
    print(status.ljust(70))


def make_on_step(grid, spawnpoint, endpoint, delay):
    def on_step(current, visited, pending):
        frame = render_grid(grid, spawnpoint, endpoint, current, visited, pending)
        status = f"Explorado: {len(visited) - len(pending)} | Frontera: {len(pending)}"
        draw_frame(frame, status)
        time.sleep(delay)

    return on_step


def animate_path(grid, spawnpoint, endpoint, path, visited, delay):
    for last_index in range(1, len(path) + 1):
        frame = render_grid(grid, spawnpoint, endpoint, visited=visited, path=path[:last_index])
        draw_frame(frame, f"Dibujando camino: {last_index}/{len(path)}")
        time.sleep(delay / 2)


def ask_integer(message, minimum, maximum):
    while True:
        try:
            value = int(input(message))
        except ValueError:
            print("Ingresá un número entero.")
            continue

        if value < minimum:
            print(f"El mínimo es {minimum}.")
        elif value > maximum:
            print(f"El máximo es {maximum}.")
        else:
            return value


def ask_delay():
    while True:
        text = input("Pausa entre pasos en segundos (Enter = 0.03): ").strip()
        if text == "":
            return 0.03
        try:
            value = float(text)
        except ValueError:
            print("Ingresá un número, por ejemplo 0.05.")
            continue

        if value >= 0:
            return value
        print("No puede ser negativo.")


def run_search(grid, spawnpoint, endpoint, algorithm_choice, delay):
    algorithm_name, search_function = ALGORITHMS[algorithm_choice]

    print(CLEAR_SCREEN)
    on_step = make_on_step(grid, spawnpoint, endpoint, delay)
    path, visited, exploration_order = search_function(grid, spawnpoint, endpoint, on_step)

    if path is None:
        frame = render_grid(grid, spawnpoint, endpoint, visited=visited)
        draw_frame(frame, f"{algorithm_name}: el mapa no tiene solución")
    else:
        animate_path(grid, spawnpoint, endpoint, path, visited, delay)
        print(f"{algorithm_name}: camino de {len(path)} casillas, "
              f"{len(exploration_order)} casillas descubiertas.")

    return {
        "path_length": None if path is None else len(path),
        "discovered": len(exploration_order),
    }


def print_comparison(results):
    print()
    print("Comparación en este mapa")
    print("-" * 40)
    print(f"{'Algoritmo':<12}{'Camino':<14}{'Descubiertas':<12}")

    for algorithm_name, result in results.items():
        path_text = "sin solución" if result["path_length"] is None else str(result["path_length"])
        print(f"{algorithm_name:<12}{path_text:<14}{result['discovered']:<12}")

    if len(results) < 2:
        return

    bfs_length = results["BFS"]["path_length"]
    dfs_length = results["DFS"]["path_length"]

    if bfs_length is None:
        print("Ningún algoritmo encontr camino: el mapa no tiene solución.")
    elif dfs_length > bfs_length:
        print(f"BFS encontró un camino {dfs_length - bfs_length} casillas más corto que DFS.")
    else:
        print("Ambos encontraron un camino del mismo largo.")


def ask_next_action():
    print()
    print("¿Quwe queres hacer ahora?")
    print("  1 = correr BFS en este mapa")
    print("  2 = correr DFS en este mapa")
    print("  3 = generar un mapa nuevo")
    print("  4 = salir")
    return ask_integer("Opción: ", 1, 4)


def main():
    init()

    while True:
        map_size = ask_integer("Tamaño del mapa (12-30): ", 12, 30)
        first_choice = ask_integer("Algoritmo (1 = BFS, 2 = DFS): ", 1, 2)
        delay = ask_delay()

        grid, spawnpoint, endpoint = generate_map(map_size)
        results = {}

        next_choice = first_choice
        while next_choice in ALGORITHMS:
            algorithm_name = ALGORITHMS[next_choice][0]
            results[algorithm_name] = run_search(grid, spawnpoint, endpoint, next_choice, delay)
            print_comparison(results)
            next_choice = ask_next_action()

        if next_choice == 4:
            break


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print(Style.RESET_ALL + "\nPrograma terminado.")
