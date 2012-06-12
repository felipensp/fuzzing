/*
 * FreeBSD syscall fuzzer - x86
 * Author: Felipe Pena <felipensp at gmail dot com>
 * Date: 2012-06-10
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <limits.h>

static const int num = 0;
static char *str;

/*
 * Argument type
 */
typedef enum {
	INT,
	STR,
	VOID,
	SIZET
} arg_types;

typedef struct _bsd_syscall_args {
	arg_types types[6];
} bsd_syscall_args;

/*
 * Argument information from src/sys/sysprot.h
 * Syscall number and name from src/sys/kern/syscalls.c
 */
struct _bsd_syscall {
	unsigned int num;
	const char *name;
	unsigned int num_args;
	bsd_syscall_args args;
} syscalls[] = {
	{3,  "read",    3, {{INT, VOID, SIZET}}},
	{4,  "write",   3, {{INT, VOID, SIZET}}},
	{5,  "open",    3, {{STR, INT, INT}}},
	{6,  "close",   1, {{INT}}},
	{7,  "wait4",   1, {{INT, INT, INT}}},
	{9,  "link",    2, {{STR, STR}}},
	{10, "unlink",  1, {{STR}}},
	{41, "dup",     1, {{INT}}},
	{51, "acct",    1, {{STR}}},
	{54, "ioctl",   3, {{INT, INT, INT}}},
	{56, "revoke",  1, {{STR}}},
	{58, "readlink",3, {{STR, STR, SIZET}}},
	{70, "sstk",    1, {{INT}}},
	{78, "mincore", 3, {{STR, SIZET, STR}}},
	{85, "swapon",  1, {{STR}}},
	{92, "fcntl",   3, {{INT, INT, INT}}},
	{93, "select",  4, {{INT, STR, STR, STR}}},
	{95, "fsync",   1, {{INT}}},
	/* End */
	{1,  "exit",    1, {{INT}}},
	{0, NULL, 0, {}}
};

typedef struct _bsd_syscall bsd_syscall;

void run_fuzzer() {
	bsd_syscall *sysc = syscalls;
	int i;

	str = malloc(sizeof(char) * 500);
	memset(str, 'A', sizeof(char) * 500);

	while (sysc->name) {
		printf("[+] Testing syscall %s(%d) (args=%d)\n",
			sysc->name, sysc->num, sysc->num_args);

		i = 0;

		/*
		 * Pushes the syscall arguments
		 */
		while (i < sysc->num_args) {
			switch (sysc->args.types[i]) {
				case INT:
				case SIZET:
					__asm__ __volatile__("movl %0, %%ecx\n"
										"pushl %%ecx" : : "m"(num) : "ecx");
					break;
				case STR:
				case VOID:
					__asm__ __volatile__("movl %0, %%ecx\n"
										"pushl %%ecx" : : "m"(str) : "ecx");
					break;
			}
			++i;
		}

		/*
		 * Sets the syscall number
		 */
		__asm__ __volatile__("movl %0, %%ebx\n"
							"pushl %%edx" : : "r"(sysc->num) : "ebx");

		/*
		 * Go kernel!
		 */
		__asm__ __volatile__("int $0x80");

		printf("[euid=%d]\n", geteuid());
		printf("[%s]\n", str);
		++sysc;
	}
}

int main(int argc, char **argv) {
	run_fuzzer();
	return 0;
}
