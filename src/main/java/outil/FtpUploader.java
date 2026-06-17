package outil;

import org.apache.commons.net.ftp.FTP;
import org.apache.commons.net.ftp.FTPClient;

import java.io.File;
import java.io.FileInputStream;
import java.io.InputStream;
import java.util.Properties;

public class FtpUploader implements Uploader {
    private final Properties props;

    public FtpUploader(Properties props) {
        this.props = props;
    }

    @Override
    public boolean upload(File mp3, Mp3Metadata metadata) {
        FTPClient ftpClient = new FTPClient();
        try {
            ftpClient.connect(props.getProperty("ftp.server"), Integer.parseInt(props.getProperty("ftp.port", "21")));
            ftpClient.login(props.getProperty("ftp.user"), props.getProperty("ftp.pass"));
            ftpClient.enterLocalPassiveMode();
            ftpClient.setFileType(FTP.BINARY_FILE_TYPE);

            String remoteDir = props.getProperty("ftp.dir", "/Mp3");
            ftpClient.changeWorkingDirectory(remoteDir);

            try (InputStream inputStream = new FileInputStream(mp3)) {
                boolean success = ftpClient.storeFile(mp3.getName(), inputStream);
                return success;
            }
        } catch (Exception e) {
            System.err.println("Erreur FTP : " + e.getMessage());
            return false;
        } finally {
            try {
                if (ftpClient.isConnected()) {
                    ftpClient.logout();
                    ftpClient.disconnect();
                }
            } catch (Exception ex) {
                ex.printStackTrace();
            }
        }
    }
}
