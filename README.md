# Running the project

Build and run the docker images

```
docker compose up -d --build
```

API is available under `http://localhost:8080/routing/{origin}/{destination}`

To run tests

```
docker compose exec php bash
./bin/phpunit
```

# Code

The code written for the challenge is on

```
/app/src/Controller/CountryRoutingController.php
/app/tests/CountryRoutingControllerTest.php
```

Everything else is the standard Symfony installation + the custom docker setup.


# Approach

For this solution I chose to create a graph out of the country border information. Once the graph is built we use Dijkstra's algorithm to do a breadth first search to find the shortest path.


# Considerations

There's a few things that could be improved in the project.

- All logic is placed in the Controller, which is not too big of a deal since it's all pretty self contained and easy to be extracted. For a production environment you'd want to do this to decouple your business logic.
- There's only tests for the Controller, defining the contract of the endpoint and all its fail states. Tests for the CountryGraph could be added to improve coverage.
- Better documentation could be added.
- The search is done with Dijkstra's algorithm, which for our purposes works pretty fast. This can be done because we're only considering adjacency, not distances between countries. If we wanted to consider that we could switch to using AStar.
- The dataset is small enough that we could consider pre-calculating all possible routes. If we consider an average of 10 countries per path and ~250 countries we would only have `250*250 / 2 (because paths are symmetrical we only need to store one of the directions) * 10 (average countries per path) * 3 bytes (length of each country string) = ~1Mb` which can easily be stored in memory.
