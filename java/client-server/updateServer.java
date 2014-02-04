package update;
import java.io.FileWriter;
import java.io.IOException;
import java.io.PrintWriter;
import java.net.InetAddress;
import java.net.ServerSocket;
import java.net.UnknownHostException;
import java.text.SimpleDateFormat;
import java.util.GregorianCalendar;

public class updateServer {
    static boolean listening = true;
    static PrintWriter log;
    static ServerSocket serverSocket = null;
    static int port = 999;
    static String host = "unknown";
    static String ip = "unknown";
    static SimpleDateFormat sdf =	new SimpleDateFormat("dd/MM/yyyy - HH:mm:ss");
    static GregorianCalendar cal = new GregorianCalendar();

    public static void init(){

  	  try {
 		   log = new PrintWriter(new FileWriter("c:\\batchnew\\upserver.log",true));
 	      }
 	   catch (IOException e) {
 		   System.err.println("errore creazione file di log");
 	   }

 	   try{
 		   host = InetAddress.getLocalHost().getHostName();
 		   ip = InetAddress.getLocalHost().getHostAddress();

 		   }
 	  catch(UnknownHostException e){
 			   System.err.println("Errore di lookup");
 	  }

    }

    public static String getTime(){
 	   cal.setTimeInMillis(System.currentTimeMillis());
 	   return sdf.format(cal.getTime());
    }

    public static void main(String[] args) throws IOException {

 	   init();

 	   try {

     	   log.println(getTime()+" init server on "+host+":"+ip);

            serverSocket = new ServerSocket(port);

            log.println(getTime()+" new serversocket create");
            log.flush();
        } catch (IOException e) {
     	   log.write(getTime()+" Porta:"+port+" in uso");
     	   log.flush();

            System.exit(-1);
        }

        while (listening){
     	   new updateServerThread(serverSocket.accept()).start();
        }

        serverSocket.close();
        log.println(getTime()+" shutdown server on "+host+":"+ip);
        log.flush();
    }

}//class
