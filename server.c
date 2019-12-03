#include <stdio.h>
#include <unistd.h>
#include <netdb.h>

int main()
{
  int sockfd;
  int new_sockfd;
  struct sockaddr_in reader_addr;
  struct sockaddr_in writer_addr;
  socklen_t writer_len = sizeof(struct sockaddr_in);

  // ソケットの生成
  if ((sockfd = socket(PF_INET, SOCK_STREAM, 0)) < 0) {
    perror("reader: socket");
    return -1;
  }

  // 通信ポート・アドレスの設定
  reader_addr.sin_family = PF_INET;
  reader_addr.sin_addr.s_addr = INADDR_ANY;
  reader_addr.sin_port = htons(12000);

  // ソケットにアドレスを結びつける
  if (bind(sockfd, (struct sockaddr *)&reader_addr, sizeof(reader_addr)) < 0) {
    perror("reader: bind");
    return -1;
  }

  // コネクト要求をいくつまで待つかを設定
  if (listen(sockfd, 5) < 0) {
    perror("reader: listen");
    close(sockfd);
    return -1;
  }

  // コネクト要求を待つ
  if ((new_sockfd = accept(sockfd,(struct sockaddr *)&writer_addr, &writer_len)) < 0) {
    perror("reader: accept");
    return -1;
  }

  // 受信
  char buf[1028];
  int rsize;
  while(1) {
    // 読み込む
    rsize = read(new_sockfd, buf, sizeof(buf));

    if (rsize == 0) {
      break;
    } else if (rsize == -1) {
      perror("reader: read");
    } else {
      printf("received > %s\n", buf);

      // 書き込む
      write(new_sockfd, buf, rsize);
    }
  }

  // ソケットを閉鎖
  close(new_sockfd);
  close(sockfd);

  return 0;
}
