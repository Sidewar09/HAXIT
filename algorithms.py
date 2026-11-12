from collections import deque

def is_walkable(grid, row, column):
    number_of_rows = len(grid)
    number_of_columns = len(grid[0])

    inside_map = 0 <= row < number_of_rows and 0 <= column < number_of_columns
    if not inside_map:
        return False

    return grid[row][column] != "#"


def get_neighbours(grid, row, column):
    directions = [
        (-1, 0),  # norte
        (1, 0),   # sur
        (0, 1),   # este
        (0, -1),  # oeste
    ]

    neighbours = []
    for row_offset, column_offset in directions:
        neighbour_row = row + row_offset
        neighbour_column = column + column_offset

        if is_walkable(grid, neighbour_row, neighbour_column):
            neighbours.append((neighbour_row, neighbour_column))

    return neighbours


def reconstruct_path(parent_of, end):
    path = []
    current = end

    while current is not None:
        path.append(current)
        current = parent_of[current]

    path.reverse()
    return path


def breadth_first_search(grid, start, end, on_step=None):
    pending = deque([start])  # cola FIFO
    visited = {start}
    parent_of = {start: None}
    exploration_order = [start]

    while pending:
        current = pending.popleft()  # saca del frente

        if on_step is not None:
            on_step(current, visited, pending)

        if current == end:
            return reconstruct_path(parent_of, end), visited, exploration_order

        for neighbour in get_neighbours(grid, *current):
            if neighbour not in visited:
                visited.add(neighbour)
                parent_of[neighbour] = current
                pending.append(neighbour)  # agrega al final
                exploration_order.append(neighbour)

    return None, visited, exploration_order


def depth_first_search(grid, start, end, on_step=None):
    pending = [start]  # pila LIFO
    visited = {start}
    parent_of = {start: None}
    exploration_order = [start]

    while pending:
        current = pending.pop()  # saca del tope

        if on_step is not None:
            on_step(current, visited, pending)

        if current == end:
            return reconstruct_path(parent_of, end), visited, exploration_order

        for neighbour in get_neighbours(grid, *current):
            if neighbour not in visited:
                visited.add(neighbour)
                parent_of[neighbour] = current
                pending.append(neighbour)  # agrega al tope
                exploration_order.append(neighbour)

    return None, visited, exploration_order
