import requests

BASE_URL = "http://127.0.0.1:5001"


def main() -> None:
    response = requests.get(f"{BASE_URL}/health", timeout=5)
    response.raise_for_status()
    print(response.json())


if __name__ == "__main__":
    main()
