CODECLIMATE_REPO_TOKEN=00886efbeafd1e15123ef6b2f8e10f6f8743494eb7b860a3075e6f439e3a3119

test:
	composer run test

coverage:
	composer run coverage
	CODECLIMATE_REPO_TOKEN=$(CODECLIMATE_REPO_TOKEN) vendor/bin/test-reporter
