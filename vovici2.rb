require 'savon'

class Vovici
  
  def initialize(username, password)
    @client = Savon::Client.new("http://efm.infosurv.vovici.net/ws/projectdata.asmx?wsdl")
    request :login, { user_name: username, password: password }
  end
  
  def request(request, params=nil)
    begin
      @client.request :pdc, request, body: params
    rescue Savon::Error => error
      puts error.to_s
    end
  end
  
end