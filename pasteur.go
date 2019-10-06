package main

import (
	"fmt"
	"log"
	"os/exec"
)

func main() {
	subprocess := exec.Command("php", "pasteur/start.php")
	out, err := subprocess.Output()
	if err != nil {
		log.Fatal(err)
	}
	fmt.Printf("%s\n", out)
}
