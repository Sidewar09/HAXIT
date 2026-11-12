import random

def generate_map(size, fill=100, bias=0.6, connect_chance=0.95, jump_chance=0.05):
    matrix = [["#"] * size for _ in range(size)]

    corners = [(0, 0), (size - 1, 0), (0, size - 1), (size - 1, size - 1)]
    spawnpoint = random.choice(corners)
    endpoint = (size - 1 - spawnpoint[0], size - 1 - spawnpoint[1])

    def is_inside_map(x_position, y_position):
        return 0 <= x_position < size and 0 <= y_position < size

    def create_safe_zone(center_coordinates):
        x_center, y_center = center_coordinates

        for x_displacement in [-1, 0, 1]:
            for y_displacement in [-1, 0, 1]:
                x_neighbour = x_center + x_displacement
                y_neighbour = y_center + y_displacement

                if is_inside_map(x_neighbour, y_neighbour):
                    matrix[x_neighbour][y_neighbour] = "."

    def random_walk(start_coordinates, steps_to_walk):
        x_position, y_position = start_coordinates
        visited_path = [start_coordinates]
        possible_directions = [(-1, 0), (1, 0), (0, 1), (0, -1)]

        for _ in range(steps_to_walk):
            x_direction, y_direction = random.choice(possible_directions)

            if random.random() < jump_chance:
                step_distance = random.choice([2, 3])
            else:
                step_distance = 1

            x_new_position = x_position + x_direction * step_distance
            y_new_position = y_position + y_direction * step_distance

            if is_inside_map(x_new_position, y_new_position):
                x_position, y_position = x_new_position, y_new_position
                matrix[x_position][y_position] = "."
                visited_path.append((x_position, y_position))

        return visited_path

    def biased_walk(start_coordinates, target_coordinates, maximum_steps):
        x_position, y_position = start_coordinates
        matrix[x_position][y_position] = "."

        for _ in range(maximum_steps):
            if (x_position, y_position) == target_coordinates:
                break

            if random.random() < bias:
                x_distance_to_target = target_coordinates[0] - x_position
                y_distance_to_target = target_coordinates[1] - y_position

                x_distance_is_pending = x_distance_to_target != 0
                y_distance_is_done = y_distance_to_target == 0
                x_axis_probability = abs(x_distance_to_target) / (
                    abs(x_distance_to_target) + abs(y_distance_to_target)
                )

                if x_distance_is_pending and (y_distance_is_done or random.random() < x_axis_probability):
                    x_position += 1 if x_distance_to_target > 0 else -1
                else:
                    y_position += 1 if y_distance_to_target > 0 else -1
            else:
                x_direction, y_direction = random.choice([(-1, 0), (1, 0), (0, 1), (0, -1)])
                if is_inside_map(x_position + x_direction, y_position + y_direction):
                    x_position += x_direction
                    y_position += y_direction

            matrix[x_position][y_position] = "."

    create_safe_zone(spawnpoint)
    create_safe_zone(endpoint)

    total_map_steps = size * size * fill // 100
    spawn_path = random_walk(spawnpoint, total_map_steps // 2)
    end_path = random_walk(endpoint, total_map_steps // 2)

    map_center = (size // 2, size // 2)

    for visited_path in (spawn_path, end_path):
        if random.random() < connect_chance:
            target_coordinates = random.choice(visited_path)
            biased_walk(map_center, target_coordinates, maximum_steps=size * size)

    matrix[spawnpoint[0]][spawnpoint[1]] = "S"
    matrix[endpoint[0]][endpoint[1]] = "E"

    return matrix, spawnpoint, endpoint
